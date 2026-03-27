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

        $typeFilter = $request->get('type', 'all');
        $targetLang = $request->get('lang', $locales[1] ?? 'de');
        $statusFilter = $request->get('status', 'untranslated');

        $items = [];

        foreach ($allModels as $type => $meta) {
            if ($typeFilter !== 'all' && $typeFilter !== $type) continue;

            $modelClass = $meta['class'];
            $records = $modelClass::all();

            foreach ($records as $record) {
                $translatableFields = $record->translatable ?? [];
                $title = $record->getTranslation($meta['titleField'], $sourceLang, false) ?: '(ohne Titel)';

                foreach ($translatableFields as $field) {
                    $sourceVal = $record->getTranslation($field, $sourceLang, false);
                    $targetVal = $record->getTranslation($field, $targetLang, false);

                    // Determine status
                    $status = $this->fieldStatus($sourceVal, $targetVal);

                    if ($statusFilter !== 'all' && $statusFilter !== $status) continue;

                    // Flatten arrays/objects for display
                    $sourceDisplay = is_array($sourceVal) ? json_encode($sourceVal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string) ($sourceVal ?? '');
                    $targetDisplay = is_array($targetVal) ? json_encode($targetVal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string) ($targetVal ?? '');

                    // Skip complex nested structures (description_blocks, content_data, details, property_details)
                    if (is_array($sourceVal) && !empty($sourceVal) && !is_string(array_values($sourceVal)[0] ?? null)) {
                        continue;
                    }

                    $items[] = [
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
                    ];
                }
            }
        }

        // Summary counts
        $counts = [
            'ok'           => count(array_filter($items, fn ($i) => $i['status'] === 'ok')),
            'untranslated' => count(array_filter($items, fn ($i) => $i['status'] === 'untranslated')),
            'inherited'    => count(array_filter($items, fn ($i) => $i['status'] === 'inherited')),
            'missing'      => count(array_filter($items, fn ($i) => $i['status'] === 'missing')),
        ];

        // Available types
        $types = collect($this->registry->all())->map(fn ($m, $k) => ['key' => $k, 'label' => $m['labelDe']])->values()->all();

        return view('admin.translations.index', [
            'items'        => $items,
            'types'        => $types,
            'typeFilter'   => $typeFilter,
            'targetLang'   => $targetLang,
            'statusFilter' => $statusFilter,
            'locales'      => $locales,
            'sourceLang'   => $sourceLang,
            'counts'       => $counts,
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

            $val = $model->getTranslation($item['field'], $request->source_lang, false);
            $text = is_string($val) ? $val : '';

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

                $model->setTranslation($item['field'], $request->target_lang, $item['text'] ?? '');
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
