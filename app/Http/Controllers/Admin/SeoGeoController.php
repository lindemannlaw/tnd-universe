<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DeepLTranslationService;
use App\Services\SeoGenerationService;
use App\Services\TranslatableModelRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SeoGeoController extends Controller
{
    public function __construct(
        private TranslatableModelRegistry $registry,
        private SeoGenerationService $seo,
        private DeepLTranslationService $deepl,
    ) {}

    public function index(Request $request): View
    {
        $locales = supported_languages_keys();
        $seoFields = TranslatableModelRegistry::SEO_FIELDS;
        $totalSlots = count($seoFields) * count($locales);

        $typeFilter   = $request->get('type', 'all');
        $rawStatus    = $request->get('status', 'all');
        $statusFilter = is_array($rawStatus) ? array_values($rawStatus) : [$rawStatus];
        $idFilter     = $request->get('id', null);

        $allItems = $this->registry->allSeoItems();

        // Compute completeness for each item
        $items = array_map(function ($item) use ($locales, $seoFields, $totalSlots) {
            $filled = 0;
            $seoPreview = [];

            foreach ($seoFields as $field) {
                foreach ($locales as $locale) {
                    $val = $item['model']->getTranslation($field, $locale, false);
                    if (filled($val)) $filled++;
                }
                // Preview the default locale value
                $seoPreview[$field] = $item['model']->getTranslation($field, config('app.fallback_locale'), false) ?: '';
            }

            $item['filled'] = $filled;
            $item['total'] = $totalSlots;
            $item['percent'] = $totalSlots > 0 ? (int) round($filled / $totalSlots * 100) : 0;
            $item['seo'] = $seoPreview;

            if ($item['percent'] === 100) $item['status'] = 'complete';
            elseif ($item['percent'] === 0) $item['status'] = 'empty';
            else $item['status'] = 'partial';

            return $item;
        }, $allItems);

        // Apply filters
        if ($typeFilter !== 'all') {
            $items = array_filter($items, fn ($i) => $i['type'] === $typeFilter);
        }
        if ($idFilter) {
            $items = array_filter($items, fn ($i) => $i['id'] == $idFilter);
        }
        if (!in_array('all', $statusFilter)) {
            $items = array_values(array_filter($items, fn ($i) => in_array($i['status'], $statusFilter)));
        }

        // Summary counts (before type/status filter for global overview)
        $complete = count(array_filter($allItems, fn ($i) => $this->quickStatus($i, $locales, $seoFields, $totalSlots) === 'complete'));
        $partial = count(array_filter($allItems, fn ($i) => $this->quickStatus($i, $locales, $seoFields, $totalSlots) === 'partial'));
        $empty = count(array_filter($allItems, fn ($i) => $this->quickStatus($i, $locales, $seoFields, $totalSlots) === 'empty'));

        // Available types for filter dropdown
        $types = collect($allItems)->pluck('type')->unique()->values()->all();

        $navPages = \App\Models\Page::whereIn('slug', [
            'about', 'services', 'portfolio', 'news', 'contacts', 'imprint', 'privacy-notice', 'terms-of-use',
        ])->pluck('id', 'slug')->all();

        $navSections = \App\Models\SiteSection::whereIn('slug', ['who-we-are', 'contact-us'])
            ->pluck('id', 'slug')->all();

        return view('admin.seo-geo.index', [
            'items'        => array_values($items),
            'types'        => $types,
            'typeFilter'   => $typeFilter,
            'idFilter'     => $idFilter,
            'statusFilter' => $statusFilter,
            'complete'     => $complete,
            'partial'      => $partial,
            'empty'        => $empty,
            'total'        => count($allItems),
            'navPages'     => $navPages,
            'navSections'  => $navSections,
        ]);
    }

    public function show(Request $request, string $type, int $id): View|JsonResponse
    {
        $model = $this->registry->resolveModel($type, $id);
        if (!$model) abort(404);

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

        return view('admin.seo-geo.show', [
            'model'    => $model,
            'type'     => $type,
            'typeLabel' => $this->registry->label($type),
            'modelId'  => $id,
            'title'    => $title,
            'fields'   => $fields,
            'locales'  => $locales,
            'seoFields' => $seoFields,
            'editUrl'  => $this->resolveEditUrl($type, $model),
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'type'   => 'required|string',
            'id'     => 'required|integer',
            'locale' => 'required|string|max:5',
        ]);

        $model = $this->registry->resolveModel($request->type, $request->id);
        if (!$model) return response()->json(['error' => 'Not found'], 404);

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
            'title'             => $model->getTranslation($meta['titleField'], $locale, false)
                                   ?: $model->getTranslation($meta['titleField'], 'en', false),
            'short_description' => in_array('short_description', $translatables) ? $getT('short_description') : '',
            'location'          => in_array('location', $translatables)          ? $getT('location')          : '',
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
            'type'      => 'required|string',
            'id'        => 'required|integer',
            'field'     => ['required', 'string', \Illuminate\Validation\Rule::in(TranslatableModelRegistry::SEO_FIELDS)],
            'locale'    => 'required|string|max:5',
            'value'     => 'nullable|string',
            'translate' => 'sometimes|boolean',
        ]);

        $model = $this->registry->resolveModel($request->type, $request->id);
        if (!$model) return response()->json(['error' => 'Not found'], 404);

        $field     = $request->field;
        $locale    = $request->locale;
        $value     = $request->value ?? '';
        $translate = (bool) $request->input('translate', false);

        try {
            DB::beginTransaction();

            $model->setTranslation($field, $locale, $value);
            $translations = [$locale => $value];

            if ($translate && $this->deepl->isConfigured() && filled($value)) {
                $allLocales   = supported_languages_keys();
                $otherLocales = array_values(array_filter($allLocales, fn($l) => $l !== $locale));
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

    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'type'    => 'required|string',
            'id'      => 'required|integer',
            'locale'  => 'required|string|max:5',
            'fields'  => 'required|array',
        ]);

        $model = $this->registry->resolveModel($request->type, $request->id);
        if (!$model) return response()->json(['error' => 'Not found'], 404);

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
            if ($this->deepl->isConfigured() && !empty($otherLocales)) {
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
            'project'          => route('admin.portfolio.projects'),
            'service'          => route('admin.services.services'),
            'service_category' => route('admin.services.categories'),
            'news_article'     => route('admin.news.articles'),
            'news_category'    => route('admin.news.categories'),
            'page'             => $this->resolvePageEditUrl($model),
            default            => route('admin.seo-geo.index'),
        };
    }

    private function resolvePageEditUrl($model): string
    {
        $slugRouteMap = [
            'home'           => 'admin.home.page',
            'about'          => 'admin.about.page',
            'contacts'       => 'admin.contacts.page',
            'imprint'        => 'admin.imprint.page',
            'privacy-notice' => 'admin.privacy-notice.page',
            'terms-of-use'   => 'admin.terms-of-use.page',
            'services'       => 'admin.services.page',
            'portfolio'      => 'admin.portfolio.page',
            'news'           => 'admin.news.page',
        ];

        $slug      = $model->slug ?? '';
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
                if (filled($item['model']->getTranslation($field, $locale, false))) $filled++;
            }
        }
        if ($filled === $totalSlots) return 'complete';
        if ($filled === 0) return 'empty';
        return 'partial';
    }
}
