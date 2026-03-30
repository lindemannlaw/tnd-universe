<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DeepLTranslationService;
use App\Services\TranslatableModelRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TranslationCheckController extends Controller
{
    public function __construct(
        private TranslatableModelRegistry $registry,
        private DeepLTranslationService $deepl,
    ) {}

    public function index(Request $request): View
    {
        $allModels = $this->registry->all();
        $locales = supported_languages_keys();
        $sourceLang = config('app.fallback_locale', 'en');

        $typeFilter   = $request->get('type', 'all');
        $targetLang   = $request->get('lang', $locales[1] ?? 'de');
        $statusFilter = $request->get('status', 'all');
        $idFilter     = $request->get('id', null);

        // Records list for the sub-filter dropdown (only when a specific type is selected)
        $typeRecords = [];
        if ($typeFilter !== 'all' && isset($allModels[$typeFilter])) {
            $meta = $allModels[$typeFilter];
            $typeRecords = $meta['class']::all()
                ->map(fn ($r) => [
                    'id'    => $r->id,
                    'title' => $r->getTranslation($meta['titleField'], $sourceLang, false) ?: '(ohne Titel)',
                ])
                ->sortBy('title')
                ->values()
                ->all();
        }

        $allItems = []; // all items before status filter (used for counts)

        foreach ($allModels as $type => $meta) {
            if ($typeFilter !== 'all' && $typeFilter !== $type) continue;

            $modelClass = $meta['class'];
            $records = $modelClass::all();

            foreach ($records as $record) {
                if ($idFilter && $record->id != $idFilter) continue;
                $translatableFields = $record->translatable ?? [];
                $title = $record->getTranslation($meta['titleField'], $sourceLang, false) ?: '(ohne Titel)';

                // Fields skipped entirely (complex/nested, not translatable as free text)
                $skipFields = [
                    'details',            // service details JSON
                    'tags',               // structured tags array
                    'info',               // structured info JSON
                ];

                // Structured JSON fields that are expanded into individual translatable sub-keys.
                // Keys mapped to null are skipped (e.g. numeric fields like year_built).
                $expandFields = [
                    'property_details' => [
                        'property_type'       => 'Property Details – Immobilien-Typ',
                        'status'              => 'Property Details – Status',
                        'year_built'          => null, // numeric, skip
                        'inquiry_button_text' => 'Property Details – Anfrage-Button',
                    ],
                ];

                foreach ($translatableFields as $field) {
                    if (in_array($field, $skipFields, true)) continue;

                    // Expand structured JSON fields into individual sub-field rows
                    if (array_key_exists($field, $expandFields)) {
                        $sourceArr = $record->getTranslation($field, $sourceLang, false) ?? [];
                        $targetArr = $record->getTranslation($field, $targetLang, false) ?? [];
                        if (!is_array($sourceArr)) $sourceArr = [];
                        if (!is_array($targetArr)) $targetArr = [];

                        foreach ($expandFields[$field] as $subKey => $subLabel) {
                            if ($subLabel === null) continue; // skip numeric/irrelevant keys
                            $subSource = $sourceArr[$subKey] ?? '';
                            $subTarget = $targetArr[$subKey] ?? '';
                            $subStatus = $this->fieldStatus($subSource, $subTarget);

                            // All locale values for multi-lang display
                            $allSubTranslations = [];
                            foreach ($locales as $tLocale) {
                                if ($tLocale === $sourceLang) continue;
                                $tArr = $record->getTranslation($field, $tLocale, false) ?? [];
                                if (!is_array($tArr)) $tArr = [];
                                $allSubTranslations[$tLocale] = (string) ($tArr[$subKey] ?? '');
                            }

                            $allItems[] = [
                                'type'         => $type,
                                'typeLabel'    => $meta['labelDe'],
                                'id'           => $record->id,
                                'title'        => $title,
                                'field'        => $field . '.' . $subKey,
                                'fieldLabel'   => $subLabel,
                                'source'       => (string) $subSource,
                                'target'       => (string) $subTarget,
                                'status'       => $subStatus,
                                'statusLabel'  => $this->statusLabel($subStatus),
                                'statusClass'  => $this->statusClass($subStatus),
                                'translations' => $allSubTranslations,
                            ];
                        }
                        continue; // done with this field
                    }

                    // Expand content_data JSON into individual translatable string leaf paths
                    if ($field === 'content_data') {
                        $sourceData = $record->getTranslation($field, $sourceLang, false) ?? [];
                        $targetData = $record->getTranslation($field, $targetLang, false) ?? [];
                        if (!is_array($sourceData)) $sourceData = [];
                        if (!is_array($targetData)) $targetData = [];

                        $paths = $this->extractJsonStringPaths($sourceData);
                        foreach ($paths as [$dotPath, $pathLabel]) {
                            $sourceText = (string) (data_get($sourceData, $dotPath) ?? '');
                            $targetText = (string) (data_get($targetData, $dotPath) ?? '');
                            $subStatus  = $this->fieldStatus($sourceText, $targetText);

                            $allSubTranslations = [];
                            foreach ($locales as $tLocale) {
                                if ($tLocale === $sourceLang) continue;
                                $tData = $record->getTranslation($field, $tLocale, false) ?? [];
                                if (!is_array($tData)) $tData = [];
                                $allSubTranslations[$tLocale] = (string) (data_get($tData, $dotPath) ?? '');
                            }

                            $allItems[] = [
                                'type'         => $type,
                                'typeLabel'    => $meta['labelDe'],
                                'id'           => $record->id,
                                'title'        => $title,
                                'field'        => $field . '.' . $dotPath,
                                'fieldLabel'   => $pathLabel,
                                'source'       => $sourceText,
                                'target'       => $targetText,
                                'status'       => $subStatus,
                                'statusLabel'  => $this->statusLabel($subStatus),
                                'statusClass'  => $this->statusClass($subStatus),
                                'translations' => $allSubTranslations,
                            ];
                        }
                        continue; // done with this field
                    }

                    // Expand description_blocks into individual translatable text paths
                    if ($field === 'description_blocks') {
                        $sourceBlocks = $record->getTranslation($field, $sourceLang, false) ?? [];
                        $targetBlocks = $record->getTranslation($field, $targetLang, false) ?? [];
                        if (!is_array($sourceBlocks)) $sourceBlocks = [];
                        if (!is_array($targetBlocks)) $targetBlocks = [];

                        $paths = $this->extractBlockTextPaths($sourceBlocks);
                        foreach ($paths as [$dotPath, $pathLabel]) {
                            $sourceText = (string) (data_get($sourceBlocks, $dotPath) ?? '');
                            $targetText = (string) (data_get($targetBlocks, $dotPath) ?? '');
                            $subStatus  = $this->fieldStatus($sourceText, $targetText);

                            $allSubTranslations = [];
                            foreach ($locales as $tLocale) {
                                if ($tLocale === $sourceLang) continue;
                                $tBlocks = $record->getTranslation($field, $tLocale, false) ?? [];
                                if (!is_array($tBlocks)) $tBlocks = [];
                                $allSubTranslations[$tLocale] = (string) (data_get($tBlocks, $dotPath) ?? '');
                            }

                            $allItems[] = [
                                'type'         => $type,
                                'typeLabel'    => $meta['labelDe'],
                                'id'           => $record->id,
                                'title'        => $title,
                                'field'        => $field . '.' . $dotPath,
                                'fieldLabel'   => $pathLabel,
                                'source'       => $sourceText,
                                'target'       => $targetText,
                                'status'       => $subStatus,
                                'statusLabel'  => $this->statusLabel($subStatus),
                                'statusClass'  => $this->statusClass($subStatus),
                                'translations' => $allSubTranslations,
                            ];
                        }
                        continue; // done with this field
                    }

                    $sourceVal = $record->getTranslation($field, $sourceLang, false);
                    $targetVal = $record->getTranslation($field, $targetLang, false);

                    // Determine status
                    $status = $this->fieldStatus($sourceVal, $targetVal);

                    // Flatten arrays/objects for display
                    $sourceDisplay = is_array($sourceVal) ? json_encode($sourceVal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string) ($sourceVal ?? '');
                    $targetDisplay = is_array($targetVal) ? json_encode($targetVal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string) ($targetVal ?? '');

                    // Skip any remaining complex nested structures
                    if (is_array($sourceVal) && !empty($sourceVal) && !is_string(array_values($sourceVal)[0] ?? null)) {
                        continue;
                    }

                    // All locale values for multi-lang display
                    $allTranslations = [];
                    foreach ($locales as $tLocale) {
                        if ($tLocale === $sourceLang) continue;
                        $tVal = $record->getTranslation($field, $tLocale, false);
                        $allTranslations[$tLocale] = is_array($tVal) ? json_encode($tVal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string) ($tVal ?? '');
                    }

                    $allItems[] = [
                        'type'          => $type,
                        'typeLabel'     => $meta['labelDe'],
                        'id'            => $record->id,
                        'title'         => $title,
                        'field'         => $field,
                        'fieldLabel'    => $this->fieldLabel($field),
                        'source'        => $sourceDisplay,
                        'target'        => $targetDisplay,
                        'status'        => $status,
                        'statusLabel'   => $this->statusLabel($status),
                        'statusClass'   => $this->statusClass($status),
                        'translations'  => $allTranslations,
                    ];
                }
            }
        }

        // Summary counts always from ALL items (independent of status filter)
        $counts = [
            'ok'           => count(array_filter($allItems, fn ($i) => $i['status'] === 'ok')),
            'untranslated' => count(array_filter($allItems, fn ($i) => $i['status'] === 'untranslated')),
            'inherited'    => count(array_filter($allItems, fn ($i) => $i['status'] === 'inherited')),
            'missing'      => count(array_filter($allItems, fn ($i) => $i['status'] === 'missing')),
        ];

        // Apply status filter for display only
        $items = $statusFilter === 'all'
            ? $allItems
            : array_values(array_filter($allItems, fn ($i) => $i['status'] === $statusFilter));

        // Available types
        $types = collect($this->registry->all())->map(fn ($m, $k) => ['key' => $k, 'label' => $m['labelDe']])->values()->all();

        return view('admin.translations.index', [
            'items'        => $items,
            'types'        => $types,
            'typeFilter'   => $typeFilter,
            'idFilter'     => $idFilter,
            'typeRecords'  => $typeRecords,
            'targetLang'   => $targetLang,
            'statusFilter' => $statusFilter,
            'locales'      => $locales,
            'sourceLang'   => $sourceLang,
            'counts'       => $counts,
            'langSettings' => \App\Models\LanguageSetting::allWithStatus($sourceLang),
        ]);
    }

    public function translate(Request $request): JsonResponse
    {
        $request->validate([
            'items'       => 'required|array',
            'items.*.type'  => 'required|string',
            'items.*.id'    => 'required|integer',
            'items.*.field' => 'required|string',
            'source_lang' => 'required|string|max:5',
            'target_lang' => 'required|string|max:5',
        ]);

        if (!$this->deepl->isConfigured()) {
            return response()->json(['error' => 'DeepL not configured'], 500);
        }

        $results = [];

        // Collect texts for batch translation
        $texts = [];
        $meta = [];
        foreach ($request->items as $i => $item) {
            $model = $this->registry->resolveModel($item['type'], $item['id']);
            if (!$model) continue;

            // Support dot-notation for sub-fields (e.g. property_details.property_type, description_blocks.0.content)
            if (str_contains($item['field'], '.')) {
                [$parentField, $subKey] = explode('.', $item['field'], 2);
                $parentVal = $model->getTranslation($parentField, $request->source_lang, false);
                $text = is_array($parentVal) ? (string) (data_get($parentVal, $subKey) ?? '') : '';
            } else {
                $val = $model->getTranslation($item['field'], $request->source_lang, false);
                $text = is_string($val) ? $val : '';
            }

            $texts[] = ['text' => $text, 'isHtml' => (bool) preg_match('/<[^>]+>/', $text)];
            $meta[] = $item;
        }

        $translated = $this->deepl->translate(
            $texts,
            strtoupper($request->source_lang),
            strtoupper($request->target_lang)
        );

        foreach ($translated as $i => $text) {
            $results[] = [
                'type'  => $meta[$i]['type'],
                'id'    => $meta[$i]['id'],
                'field' => $meta[$i]['field'],
                'text'  => $text,
            ];
        }

        return response()->json(['translations' => $results]);
    }

    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'items'        => 'required|array',
            'items.*.type'  => 'required|string',
            'items.*.id'    => 'required|integer',
            'items.*.field' => 'required|string',
            'items.*.text'  => 'nullable|string',
            'target_lang'  => 'required|string|max:5',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->items as $item) {
                $model = $this->registry->resolveModel($item['type'], $item['id']);
                if (!$model) continue;

                // Support dot-notation for sub-fields (e.g. property_details.property_type, description_blocks.0.content)
                if (str_contains($item['field'], '.')) {
                    [$parentField, $subKey] = explode('.', $item['field'], 2);
                    $existing = $model->getTranslation($parentField, $request->target_lang, false) ?? [];
                    if (!is_array($existing)) $existing = [];

                    // For JSON fields with nested structure: initialise target from EN source if empty,
                    // so non-text nodes (image URLs, types, numeric values, etc.) are preserved.
                    if (in_array($parentField, ['description_blocks', 'content_data'], true) && empty($existing)) {
                        $sourceLang = config('app.fallback_locale', 'en');
                        $sourceBlocks = $model->getTranslation($parentField, $sourceLang, false) ?? [];
                        $existing = is_array($sourceBlocks) ? json_decode(json_encode($sourceBlocks), true) : [];
                    }

                    data_set($existing, $subKey, $item['text'] ?? '');
                    $model->setTranslation($parentField, $request->target_lang, $existing);
                } else {
                    $model->setTranslation($item['field'], $request->target_lang, $item['text'] ?? '');
                }
                $model->save();
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Übersetzungen gespeichert']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[TranslationCheck] Apply failed', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Recursively extract all string leaf paths from an arbitrary JSON array.
     * Numeric-indexed arrays (e.g. phone/email lists) are skipped entirely,
     * keeping only editorially meaningful string values.
     *
     * @param  array  $data       The decoded JSON array (for the source locale)
     * @param  string $prefix     Dot-notation prefix accumulated during recursion
     * @return array<int, array{0: string, 1: string}>  [dotPath, label]
     */
    private function extractJsonStringPaths(array $data, string $prefix = ''): array
    {
        $paths = [];

        foreach ($data as $key => $value) {
            // Skip numeric-indexed arrays (phone lists, email lists, etc.)
            if (is_int($key)) {
                return [];
            }

            $dotPath = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_string($value) && $value !== '') {
                // Readable label: replace dots with " / " and title-case
                $label = 'Content – ' . str_replace('.', ' / ', $dotPath);
                $paths[] = [$dotPath, $label];
            } elseif (is_array($value)) {
                // Recurse only into associative arrays
                $nested = $this->extractJsonStringPaths($value, $dotPath);
                $paths  = array_merge($paths, $nested);
            }
            // booleans, integers, null → skip
        }

        return $paths;
    }

    /**
     * Extract all translatable text paths from a description_blocks array.
     * Each returned tuple is [dot-notation path, human-readable German label].
     * Paths are unique per block index, guaranteeing single-source-of-truth lookup.
     *
     * @param  array $blocks
     * @return array<int, array{0: string, 1: string}>
     */
    private function extractBlockTextPaths(array $blocks): array
    {
        $paths = [];

        foreach ($blocks as $blockIdx => $block) {
            $type   = $block['type'] ?? '';
            $prefix = (string) $blockIdx;
            $bNum   = $blockIdx + 1;

            switch ($type) {
                case 'text':
                    if (!empty($block['content'])) {
                        $paths[] = ["{$prefix}.content", "Block {$bNum} – Text"];
                    }
                    break;

                case 'text_column_row':
                    foreach ($block['items'] ?? [] as $itemIdx => $item) {
                        $ip   = "{$prefix}.items.{$itemIdx}";
                        $col  = $itemIdx + 1;
                        if (!empty($item['headline'])) {
                            $paths[] = ["{$ip}.headline",  "Block {$bNum} – Spalte {$col} Titel"];
                        }
                        if (!empty($item['subhead'])) {
                            $paths[] = ["{$ip}.subhead",   "Block {$bNum} – Spalte {$col} Untertitel"];
                        }
                        if (!empty($item['content'])) {
                            $paths[] = ["{$ip}.content",   "Block {$bNum} – Spalte {$col} Text"];
                        }
                        if (!empty($item['link_text'])) {
                            $paths[] = ["{$ip}.link_text", "Block {$bNum} – Spalte {$col} Link-Text"];
                        }
                    }
                    break;

                case 'floating_gallery':
                    foreach ($block['items'] ?? [] as $itemIdx => $item) {
                        $ip  = "{$prefix}.items.{$itemIdx}";
                        $img = $itemIdx + 1;
                        if (!empty($item['headline'])) {
                            $paths[] = ["{$ip}.headline", "Block {$bNum} – Bild {$img} Titel"];
                        }
                        if (!empty($item['subhead'])) {
                            $paths[] = ["{$ip}.subhead",  "Block {$bNum} – Bild {$img} Untertitel"];
                        }
                    }
                    break;

                case 'numbers':
                    if (!empty($block['headline'])) {
                        $paths[] = ["{$prefix}.headline", "Block {$bNum} – Kennzahlen Titel"];
                    }
                    foreach ($block['items'] ?? [] as $itemIdx => $item) {
                        $ip  = "{$prefix}.items.{$itemIdx}";
                        $num = $itemIdx + 1;
                        if (!empty($item['subline'])) {
                            $paths[] = ["{$ip}.subline", "Block {$bNum} – Zahl {$num} Subline"];
                        }
                        if (!empty($item['title'])) {
                            $paths[] = ["{$ip}.title",   "Block {$bNum} – Zahl {$num} Bezeichnung"];
                        }
                    }
                    break;

                case 'video':
                    if (!empty($block['headline'])) {
                        $paths[] = ["{$prefix}.headline", "Block {$bNum} – Video Titel"];
                    }
                    if (!empty($block['content'])) {
                        $paths[] = ["{$prefix}.content",  "Block {$bNum} – Video Text"];
                    }
                    break;

                // embed, image, etc. have no free-text fields worth translating
            }
        }

        return $paths;
    }

    private function fieldStatus(mixed $sourceVal, mixed $targetVal): string
    {
        $sourceEmpty = !filled($sourceVal);
        $targetEmpty = !filled($targetVal);

        if ($sourceEmpty && $targetEmpty) return 'missing';
        if ($sourceEmpty) return 'missing';
        if ($targetEmpty) return 'untranslated';
        if ($sourceVal == $targetVal) return 'inherited';
        return 'ok';
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'title'             => 'Titel',
            'name'              => 'Name',
            'inner_title'       => 'Inner Title',
            'short_description' => 'Kurzbeschreibung',
            'description'       => 'Beschreibung',
            'seo_title'         => 'SEO Titel',
            'seo_description'   => 'SEO Beschreibung',
            'seo_keywords'      => 'SEO Keywords',
            'geo_text'          => 'GEO Text',
            'location'          => 'Standort',
            'tags'              => 'Tags',
            'info'              => 'Info',
            'content_data'      => 'Content',
            default             => $field,
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'ok'           => 'OK',
            'untranslated' => 'Nicht übersetzt',
            'inherited'    => 'Geerbt',
            'missing'      => 'Fehlend',
            default        => $status,
        };
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            'ok'           => 'bg-success',
            'untranslated' => 'bg-danger',
            'inherited'    => 'bg-info',
            'missing'      => 'bg-warning text-dark',
            default        => 'bg-secondary',
        };
    }
}
