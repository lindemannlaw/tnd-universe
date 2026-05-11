<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DeepLTranslationService;
use App\Services\GoogleIndexingApiService;
use App\Services\IndexNowService;
use App\Services\SeoGenerationService;
use App\Services\SitemapGeneratorService;
use App\Services\TranslatableModelRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SeoGeoController extends Controller
{
    public function __construct(
        private TranslatableModelRegistry $registry,
        private SeoGenerationService $seo,
        private DeepLTranslationService $deepl,
        private SitemapGeneratorService $sitemap,
        private IndexNowService $indexNow,
        private GoogleIndexingApiService $googleIndexing,
    ) {}

    public function index(Request $request): View
    {
        $locales = supported_languages_keys();
        $seoFields = TranslatableModelRegistry::SEO_FIELDS;
        $totalSlots = count($seoFields) * count($locales);

        $typeFilter = $request->get('type', 'all');
        $rawStatus = $request->get('status', 'all');
        $statusFilter = is_array($rawStatus) ? array_values($rawStatus) : [$rawStatus];
        $idFilter = $request->get('id', null);

        $allItems = $this->registry->allSeoItems();

        // Compute completeness for each item
        $items = array_map(function ($item) use ($locales, $seoFields, $totalSlots) {
            $filled = 0;
            $seoPreview = [];

            foreach ($seoFields as $field) {
                foreach ($locales as $locale) {
                    $val = $item['model']->getTranslation($field, $locale, false);
                    if (filled($val)) {
                        $filled++;
                    }
                }
                // Preview the default locale value
                $seoPreview[$field] = $item['model']->getTranslation($field, config('app.fallback_locale'), false) ?: '';
            }

            $item['filled'] = $filled;
            $item['total'] = $totalSlots;
            $item['percent'] = $totalSlots > 0 ? (int) round($filled / $totalSlots * 100) : 0;
            $item['seo'] = $seoPreview;

            if ($item['percent'] === 100) {
                $item['status'] = 'complete';
            } elseif ($item['percent'] === 0) {
                $item['status'] = 'empty';
            } else {
                $item['status'] = 'partial';
            }

            return $item;
        }, $allItems);

        // Apply filters
        if ($typeFilter !== 'all') {
            $items = array_filter($items, fn ($i) => $i['type'] === $typeFilter);
        }
        if ($idFilter) {
            $items = array_filter($items, fn ($i) => $i['id'] == $idFilter);
        }
        if (! in_array('all', $statusFilter)) {
            $items = array_values(array_filter($items, fn ($i) => in_array($i['status'], $statusFilter)));
        }

        // Summary counts (before type/status filter for global overview)
        $complete = count(array_filter($allItems, fn ($i) => $this->quickStatus($i, $locales, $seoFields, $totalSlots) === 'complete'));
        $partial = count(array_filter($allItems, fn ($i) => $this->quickStatus($i, $locales, $seoFields, $totalSlots) === 'partial'));
        $empty = count(array_filter($allItems, fn ($i) => $this->quickStatus($i, $locales, $seoFields, $totalSlots) === 'empty'));

        // Available types for filter dropdown
        $types = collect($allItems)->pluck('type')->unique()->values()->all();

        $navPages = \App\Models\Page::whereIn('slug', [
            'home', 'about', 'services', 'portfolio', 'news', 'contacts', 'imprint', 'privacy-notice', 'terms-of-use',
        ])->pluck('id', 'slug')->all();

        $navSections = \App\Models\SiteSection::whereIn('slug', ['who-we-are', 'contact-us'])
            ->pluck('id', 'slug')->all();

        return view('admin.seo-geo.index', [
            'items' => array_values($items),
            'types' => $types,
            'typeFilter' => $typeFilter,
            'idFilter' => $idFilter,
            'statusFilter' => $statusFilter,
            'complete' => $complete,
            'partial' => $partial,
            'empty' => $empty,
            'total' => count($allItems),
            'navPages' => $navPages,
            'navSections' => $navSections,
        ]);
    }

    public function show(Request $request, string $type, int $id): View|JsonResponse
    {
        $model = $this->registry->resolveModel($type, $id);
        if (! $model) {
            abort(404);
        }

        $meta = $this->registry->resolve($type);
        $locales = supported_languages_keys();
        $seoFields = TranslatableModelRegistry::SEO_FIELDS;

        $fields = [];
        foreach ($seoFields as $field) {
            $values = [];
            foreach ($locales as $locale) {
                $values[$locale] = $model->getTranslation($field, $locale, false) ?: '';
            }
            $fields[$field] = $values;
        }

        $title = $model->getTranslation($meta['titleField'], config('app.fallback_locale'), false) ?: '(ohne Titel)';

        if ($request->ajax()) {
            return response()->json(['fields' => $fields, 'title' => $title]);
        }

        $hasGeoColumns = $this->modelHasGeoCoords($model);
        $geo = [
            'lat' => $hasGeoColumns ? ($model->lat ?? '') : '',
            'lon' => $hasGeoColumns ? ($model->lon ?? '') : '',
            'geo_region' => $hasGeoColumns ? ($model->geo_region ?? '') : '',
        ];

        return view('admin.seo-geo.show', [
            'model' => $model,
            'type' => $type,
            'typeLabel' => $this->registry->label($type),
            'modelId' => $id,
            'title' => $title,
            'fields' => $fields,
            'locales' => $locales,
            'seoFields' => $seoFields,
            'editUrl' => $this->resolveEditUrl($type, $model),
            'canGoogleReindex' => $this->resolvePublicPath($type, $model) !== null,
            'geo' => $geo,
            'hasGeoColumns' => $hasGeoColumns,
        ]);
    }

    private function modelHasGeoCoords($model): bool
    {
        $fillable = method_exists($model, 'getFillable') ? $model->getFillable() : [];
        return in_array('lat', $fillable, true)
            && in_array('lon', $fillable, true)
            && in_array('geo_region', $fillable, true);
    }

    /**
     * Build all locale public URLs for a model (used by googleReindex).
     *
     * @return array<string, string> locale => public URL
     */
    private function buildPublicUrlsForLocales(string $type, $model): array
    {
        $path = $this->resolvePublicPath($type, $model);
        if ($path === null) {
            return [];
        }

        $base = rtrim((string) config('app.url'), '/');
        $defaultLocale = (string) config('app.fallback_locale', 'en');
        $hideDefault = (bool) config('laravellocalization.hideDefaultLocaleInURL', true);

        $urls = [];
        foreach (supported_languages_keys() as $locale) {
            $urls[$locale] = $this->buildLocalizedUrl($base, $path, $locale, $defaultLocale, $hideDefault);
        }

        return $urls;
    }

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
            'locale' => 'required|string|max:5',
        ]);

        $model = $this->registry->resolveModel($request->type, $request->id);
        if (! $model) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $meta = $this->registry->resolve($request->type);
        $locale = $request->locale;

        $translatables = method_exists($model, 'getTranslatableAttributes')
            ? $model->getTranslatableAttributes()
            : (property_exists($model, 'translatable') ? $model->translatable : []);

        $getT = function (string $attr) use ($model, $locale): string {
            try {
                return $model->getTranslation($attr, $locale, false) ?: '';
            } catch (\Throwable) {
                return '';
            }
        };

        $context = [
            'title' => $model->getTranslation($meta['titleField'], $locale, false)
                                   ?: $model->getTranslation($meta['titleField'], 'en', false),
            'short_description' => in_array('short_description', $translatables) ? $getT('short_description') : '',
            'location' => in_array('location', $translatables) ? $getT('location') : '',
        ];

        // Add property details for projects
        if ($request->type === 'project') {
            $details = $model->getTranslation('property_details', $locale, false);
            if (is_array($details)) {
                $context['property_type'] = $details['property_type'] ?? '';
            }
            $context['area'] = $model->area ?? '';
        }

        $result = $this->seo->generate($context, $locale);

        return response()->json($result);
    }

    public function saveField(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
            'field' => ['required', 'string', \Illuminate\Validation\Rule::in(TranslatableModelRegistry::SEO_FIELDS)],
            'locale' => 'required|string|max:5',
            'value' => 'nullable|string',
            'translate' => 'sometimes|boolean',
        ]);

        $model = $this->registry->resolveModel($request->type, $request->id);
        if (! $model) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $field = $request->field;
        $locale = $request->locale;
        $value = $request->value ?? '';
        $translate = (bool) $request->input('translate', false);

        try {
            DB::beginTransaction();

            $model->setTranslation($field, $locale, $value);
            $translations = [$locale => $value];

            if ($translate && $this->deepl->isConfigured() && filled($value)) {
                $allLocales = supported_languages_keys();
                $otherLocales = array_values(array_filter($allLocales, fn ($l) => $l !== $locale));
                $items = [['text' => $value, 'isHtml' => false]];

                foreach ($otherLocales as $targetLang) {
                    $translated = $this->deepl->translate($items, strtoupper($locale), strtoupper($targetLang));
                    $translatedValue = $translated[0] ?? $value;
                    $model->setTranslation($field, $targetLang, $translatedValue);
                    $translations[$targetLang] = $translatedValue;
                }
            }

            $model->save();
            DB::commit();

            return response()->json(['success' => true, 'translations' => $translations]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[SeoGeo] saveField failed', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function geocode(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:2000',
        ]);

        $query = trim((string) $request->input('query'));

        // 1. URL? (e.g. maps.app.goo.gl, google.com/maps, goo.gl/maps)
        if (preg_match('#https?://\S+#', $query, $m)) {
            $coords = $this->coordsFromMapsUrl($m[0]);
            if ($coords) {
                return response()->json([
                    'found' => true,
                    'lat' => $coords['lat'],
                    'lon' => $coords['lon'],
                    'geo_region' => $this->countryCodeFromCoords($coords['lat'], $coords['lon']),
                    'source' => 'maps-url',
                    'display_name' => $coords['display_name'] ?? null,
                ]);
            }
        }

        // 2. DMS? e.g. 18°27'08.6"N 64°26'26.5"W
        $dms = $this->coordsFromDms($query);
        if ($dms) {
            return response()->json([
                'found' => true,
                'lat' => $dms['lat'],
                'lon' => $dms['lon'],
                'geo_region' => $this->countryCodeFromCoords($dms['lat'], $dms['lon']),
                'source' => 'dms',
            ]);
        }

        // 3. Plain decimal "lat, lon"
        if (preg_match('/^\s*(-?\d{1,3}(?:\.\d+)?)\s*[,;\s]\s*(-?\d{1,3}(?:\.\d+)?)\s*$/', $query, $m)) {
            $lat = (float) $m[1];
            $lon = (float) $m[2];
            if ($lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180) {
                return response()->json([
                    'found' => true,
                    'lat' => $lat,
                    'lon' => $lon,
                    'geo_region' => $this->countryCodeFromCoords($lat, $lon),
                    'source' => 'decimal',
                ]);
            }
        }

        // 4. Fall back to Nominatim place-name search
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'TND-Universe-Admin/1.0 (+'.config('app.url').')',
                'Accept' => 'application/json',
            ])
                ->timeout(10)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1,
                ]);

            if (! $response->successful()) {
                return response()->json([
                    'error' => 'Geocoding service returned HTTP '.$response->status(),
                ], 502);
            }

            $results = $response->json();
            if (! is_array($results) || empty($results)) {
                return response()->json([
                    'found' => false,
                    'message' => 'No location matched. Try a place name, Google Maps URL, or "lat, lon".',
                ]);
            }

            $first = $results[0];
            $countryCode = strtoupper((string) ($first['address']['country_code'] ?? ''));

            return response()->json([
                'found' => true,
                'lat' => (float) $first['lat'],
                'lon' => (float) $first['lon'],
                'geo_region' => $countryCode ?: null,
                'display_name' => $first['display_name'] ?? null,
                'source' => 'nominatim',
            ]);
        } catch (\Throwable $e) {
            Log::error('[SeoGeo] geocode failed', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Try to extract decimal lat/lon from a Google Maps URL (short or full).
     */
    private function coordsFromMapsUrl(string $url): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) TND-Universe-Admin/1.0',
                'Accept' => 'text/html,*/*;q=0.5',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
                ->timeout(10)
                ->withOptions(['allow_redirects' => ['max' => 5, 'track_redirects' => true]])
                ->get($url);

            $body = (string) $response->body();
            $finalUrl = $response->effectiveUri()?->__toString() ?? $url;

            // Scan both the final URL and the page body for known coord patterns.
            $haystacks = [$finalUrl, $body];

            $patterns = [
                // /@LAT,LON,ZOOMz/ — view URL
                '#/@(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)(?:,\d+(?:\.\d+)?z)?#',
                // !3d{lat}!4d{lon} — marker encoding (most reliable)
                '#!3d(-?\d{1,3}\.\d+)!4d(-?\d{1,3}\.\d+)#',
                // ?q=LAT,LON or &q=LAT,LON
                '#[?&]q=(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)#',
                // /place/.../LAT,LON
                '#/(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)(?:[,/&?]|$)#',
            ];

            foreach ($haystacks as $hay) {
                foreach ($patterns as $p) {
                    if (preg_match($p, $hay, $m)) {
                        $lat = (float) $m[1];
                        $lon = (float) $m[2];
                        if ($lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180 && ! ($lat == 0 && $lon == 0)) {
                            return [
                                'lat' => $lat,
                                'lon' => $lon,
                                'display_name' => $this->extractPlaceNameFromMapsUrl($finalUrl),
                            ];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[SeoGeo] coordsFromMapsUrl failed', ['url' => $url, 'message' => $e->getMessage()]);
        }

        return null;
    }

    private function extractPlaceNameFromMapsUrl(string $url): ?string
    {
        if (preg_match('#/place/([^/]+)/#', $url, $m)) {
            return rawurldecode($m[1]);
        }
        return null;
    }

    /**
     * Parse DMS notation like: 18°27'08.6"N 64°26'26.5"W
     */
    private function coordsFromDms(string $input): ?array
    {
        $pattern = '/(\d{1,3})\s*°\s*(\d{1,2})\s*[\'’]\s*(\d{1,2}(?:\.\d+)?)\s*[\"”″]?\s*([NSEW])/iu';
        if (! preg_match_all($pattern, $input, $matches, PREG_SET_ORDER) || count($matches) < 2) {
            return null;
        }

        $lat = null;
        $lon = null;
        foreach ($matches as $m) {
            $deg = (float) $m[1];
            $min = (float) $m[2];
            $sec = (float) $m[3];
            $hem = strtoupper($m[4]);
            $dec = $deg + $min / 60.0 + $sec / 3600.0;
            if ($hem === 'S' || $hem === 'W') {
                $dec = -$dec;
            }
            if ($hem === 'N' || $hem === 'S') {
                $lat = $dec;
            } else {
                $lon = $dec;
            }
        }

        if ($lat === null || $lon === null) {
            return null;
        }
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return null;
        }

        return [
            'lat' => round($lat, 7),
            'lon' => round($lon, 7),
        ];
    }

    /**
     * Best-effort reverse geocode to ISO 3166-1 alpha-2 via Nominatim.
     */
    private function countryCodeFromCoords(float $lat, float $lon): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'TND-Universe-Admin/1.0 (+'.config('app.url').')',
                'Accept' => 'application/json',
            ])
                ->timeout(8)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat,
                    'lon' => $lon,
                    'format' => 'json',
                    'zoom' => 3,
                    'addressdetails' => 1,
                ]);
            if (! $response->successful()) return null;
            $data = $response->json();
            $code = strtoupper((string) ($data['address']['country_code'] ?? ''));
            return $code ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function saveGeo(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
            'lat' => 'nullable|numeric|between:-90,90',
            'lon' => 'nullable|numeric|between:-180,180',
            'geo_region' => 'nullable|string|max:8',
        ]);

        $model = $this->registry->resolveModel($request->type, $request->id);
        if (! $model) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $fillable = method_exists($model, 'getFillable') ? $model->getFillable() : [];

        try {
            foreach (['lat', 'lon', 'geo_region'] as $col) {
                if (in_array($col, $fillable, true) || $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $col)) {
                    $value = $request->input($col);
                    $model->{$col} = ($value === '' || $value === null) ? null : $value;
                }
            }
            $model->save();

            return response()->json([
                'success' => true,
                'values' => [
                    'lat' => $model->lat,
                    'lon' => $model->lon,
                    'geo_region' => $model->geo_region,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[SeoGeo] saveGeo failed', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
            'locale' => 'required|string|max:5',
            'fields' => 'required|array',
        ]);

        $model = $this->registry->resolveModel($request->type, $request->id);
        if (! $model) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $sourceLang = $request->locale;
        $allLocales = supported_languages_keys();
        $otherLocales = array_filter($allLocales, fn ($l) => $l !== $sourceLang);

        try {
            DB::beginTransaction();

            // Save source locale values
            foreach ($request->fields as $field => $value) {
                if (in_array($field, TranslatableModelRegistry::SEO_FIELDS)) {
                    $model->setTranslation($field, $sourceLang, $value);
                }
            }

            // Translate to all other languages via DeepL
            if ($this->deepl->isConfigured() && ! empty($otherLocales)) {
                $sourceTexts = [];
                $fieldOrder = [];
                foreach ($request->fields as $field => $value) {
                    if (in_array($field, TranslatableModelRegistry::SEO_FIELDS) && filled($value)) {
                        $sourceTexts[] = ['text' => $value, 'isHtml' => false];
                        $fieldOrder[] = $field;
                    }
                }

                foreach ($otherLocales as $targetLang) {
                    $deeplTarget = strtoupper($targetLang);
                    $translated = $this->deepl->translate($sourceTexts, strtoupper($sourceLang), $deeplTarget);

                    foreach ($translated as $i => $text) {
                        $model->setTranslation($fieldOrder[$i], $targetLang, $text);
                    }
                }
            }

            $model->save();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Gespeichert und übersetzt']);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[SeoGeo] Apply failed', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function resolveEditUrl(string $type, $model): string
    {
        return match ($type) {
            'project' => route('admin.portfolio.projects', ['edit' => $model->id]),
            'service' => route('admin.services.services', ['edit' => $model->id]),
            'service_category' => route('admin.services.categories', ['edit' => $model->id]),
            'news_article' => route('admin.news.articles', ['edit' => $model->id]),
            'news_category' => route('admin.news.categories', ['edit' => $model->id]),
            'page' => $this->resolvePageEditUrl($model),
            default => route('admin.seo-geo.index'),
        };
    }

    private function resolvePageEditUrl($model): string
    {
        $slugRouteMap = [
            'home' => 'admin.home.page',
            'about' => 'admin.about.page',
            'contacts' => 'admin.contacts.page',
            'imprint' => 'admin.imprint.page',
            'privacy-notice' => 'admin.privacy-notice.page',
            'terms-of-use' => 'admin.terms-of-use.page',
            'services' => 'admin.services.page',
            'portfolio' => 'admin.portfolio.page',
            'news' => 'admin.news.page',
        ];

        $slug = $model->slug ?? '';
        $routeName = $slugRouteMap[$slug] ?? null;

        return $routeName && \Illuminate\Support\Facades\Route::has($routeName)
            ? route($routeName)
            : route('admin.seo-geo.index');
    }

    private function quickStatus(array $item, array $locales, array $seoFields, int $totalSlots): string
    {
        $filled = 0;
        foreach ($seoFields as $field) {
            foreach ($locales as $locale) {
                if (filled($item['model']->getTranslation($field, $locale, false))) {
                    $filled++;
                }
            }
        }
        if ($filled === $totalSlots) {
            return 'complete';
        }
        if ($filled === 0) {
            return 'empty';
        }

        return 'partial';
    }

    public function livePreview(Request $request, string $type, int $id, ?string $refresh = null): JsonResponse
    {
        $model = $this->registry->resolveModel($type, $id);
        if (! $model) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $path = $this->resolvePublicPath($type, $model);
        if ($path === null) {
            return response()->json([
                'supported' => false,
                'message' => 'Dieser Inhaltstyp hat keine direkte öffentliche URL für eine Live-Vorschau.',
            ]);
        }

        $base = rtrim((string) config('app.url'), '/');
        $defaultLocale = (string) config('app.fallback_locale', 'en');
        $hideDefault = (bool) config('laravellocalization.hideDefaultLocaleInURL', true);
        $locales = supported_languages_keys();
        $force = $request->boolean('refresh');

        $results = [];
        foreach ($locales as $locale) {
            $url = $this->buildLocalizedUrl($base, $path, $locale, $defaultLocale, $hideDefault);
            $cacheKey = 'seo:live-preview:'.md5($url);

            if ($force) {
                Cache::forget($cacheKey);
            }

            $data = Cache::remember($cacheKey, 60, function () use ($url) {
                try {
                    $response = Http::withHeaders([
                        'User-Agent' => 'TNDBackofficeBot/1.0 (+https://tnduniverse.com)',
                        'Accept' => 'text/html,application/xhtml+xml',
                    ])->timeout(10)->get($url);

                    $html = (string) $response->body();
                    $title = null;
                    $description = null;

                    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
                        $title = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                    if (preg_match_all('/<meta\b[^>]*>/i', $html, $tags)) {
                        foreach ($tags[0] as $tag) {
                            if (preg_match('/\bname\s*=\s*["\']description["\']/i', $tag)
                                && preg_match('/\bcontent\s*=\s*"([^"]*)"|\bcontent\s*=\s*\'([^\']*)\'/i', $tag, $cm)) {
                                $description = html_entity_decode(trim($cm[1] !== '' ? $cm[1] : ($cm[2] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                break;
                            }
                        }
                    }

                    return [
                        'status' => $response->status(),
                        'title' => $title,
                        'description' => $description,
                        'fetched_at' => now()->toIso8601String(),
                        'error' => null,
                    ];
                } catch (\Throwable $e) {
                    return [
                        'status' => 0,
                        'title' => null,
                        'description' => null,
                        'fetched_at' => now()->toIso8601String(),
                        'error' => $e->getMessage(),
                    ];
                }
            });

            $results[$locale] = array_merge(['url' => $url], $data);
        }

        return response()->json([
            'supported' => true,
            'results' => $results,
        ]);
    }

    private function resolvePublicPath(string $type, $model): ?string
    {
        return match ($type) {
            'page' => $this->resolvePagePath($model->slug ?? ''),
            'project' => filled($model->slug ?? null) ? '/portfolio/'.$model->slug : null,
            'service' => filled($model->slug ?? null) ? '/services/'.$model->slug : null,
            'news_article' => filled($model->slug ?? null) ? '/news/'.$model->slug : null,
            default => null,
        };
    }

    private function resolvePagePath(string $slug): ?string
    {
        if ($slug === '') {
            return null;
        }

        if (in_array($slug, static_page_editable_slugs(), true)) {
            return static_page_url($slug);
        }

        return match ($slug) {
            'home' => '/',
            'about', 'services', 'portfolio',
            'news', 'contacts', 'imprint',
            'privacy-notice', 'terms-of-use' => '/'.$slug,
            default => null,
        };
    }

    private function buildLocalizedUrl(string $base, string $path, string $locale, string $defaultLocale, bool $hideDefault): string
    {
        if ($locale === $defaultLocale && $hideDefault) {
            return $path === '/' ? $base.'/' : $base.$path;
        }

        return $path === '/' ? $base.'/'.$locale : $base.'/'.$locale.$path;
    }

    public function triggerCrawl(): JsonResponse
    {
        Cache::forget('sitemap:index');
        foreach (SitemapGeneratorService::TYPES as $type) {
            Cache::forget("sitemap:{$type}");
        }

        $sitemapUrl = rtrim((string) config('app.url'), '/').'/sitemap.xml';

        $urls = $this->sitemap->allPublicUrls();
        $indexNowResult = $this->indexNow->submit($urls);

        return response()->json([
            'sitemap' => [
                'status' => 'success',
                'url' => $sitemapUrl,
                'urls_count' => count($urls),
                'message' => 'Sitemap-Cache geleert. '.count($urls).' URL(s) bereit.',
            ],
            'indexnow' => $indexNowResult,
        ]);
    }

    /**
     * Push all locale URLs of a single model to Google's Indexing API in one shot.
     * No further user action required (no Search Console step).
     */
    public function googleReindex(string $type, int $id): JsonResponse
    {
        $model = $this->registry->resolveModel($type, $id);
        if (! $model) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $urls = $this->buildPublicUrlsForLocales($type, $model);
        if (empty($urls)) {
            return response()->json([
                'status' => 'unsupported',
                'message' => 'Dieser Inhaltstyp hat keine direkte öffentliche URL.',
            ], 422);
        }

        $result = $this->googleIndexing->submit(array_values($urls), 'URL_UPDATED');

        return response()->json([
            'status' => $result['status'],
            'submitted' => $result['submitted'] ?? 0,
            'skipped' => $result['skipped'] ?? 0,
            'urls_count' => count($urls),
            'locales' => array_keys($urls),
            'message' => $result['message'] ?? '',
        ]);
    }
}
