<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * When saving a model from an EN-only form, merge the submitted locale
     * values with the existing translations so DE/FR/PL etc. are preserved.
     */
    protected function preserveTranslations(\Illuminate\Database\Eloquent\Model $model, array &$data): void
    {
        foreach ($model->translatable ?? [] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $existing    = $model->getTranslations($field);
                $data[$field] = array_merge($existing, $data[$field]);
            }
        }
    }

    /**
     * Build the autoTranslate payload for a freshly created record (store).
     * All content fields are included; SEO/GEO is regenerated natively per
     * locale in phase 2 and does not need to be listed in the payload.
     */
    protected function buildAutoTranslatePayload(
        \Illuminate\Database\Eloquent\Model $model,
        string $type,
        array  $contentFields,
        bool   $hasSeo,
        string $editRoute,
        array  $editRouteParams = [],
    ): array {
        $sourceLang  = config('app.fallback_locale', 'en');
        $targetLangs = array_values(array_filter(
            supported_languages_keys(),
            fn($l) => $l !== $sourceLang
        ));

        return [
            'type'           => $type,
            'id'             => $model->id,
            'isUpdate'       => false,
            'translateUrl'   => route('admin.translations.translate'),
            'applyUrl'       => route('admin.translations.apply'),
            'geoGenerateUrl' => $hasSeo ? route('admin.seo-geo.generate') : null,
            'hasSeo'         => $hasSeo,
            'sourceLang'     => $sourceLang,
            'targetLangs'    => $targetLangs,
            'contentFields'  => $contentFields,
            'editUrl'        => route($editRoute, $editRouteParams),
            'translationsUrl' => route('admin.translations.index', ['type' => $type, 'id' => $model->id]),
            'seoGeoUrl'      => $hasSeo ? route('admin.seo-geo.index', ['type' => $type, 'id' => $model->id]) : null,
            'unchangedCount' => 0,
            'changedFields'  => $contentFields,
        ];
    }

    /**
     * Build the autoTranslate payload for an update, detecting which source-lang
     * fields have changed (new value ≠ old value, OR was empty and now filled).
     */
    protected function buildAutoTranslateUpdatePayload(
        \Illuminate\Database\Eloquent\Model $freshModel,
        array  $oldValues,        // [field => oldSourceValue]  captured BEFORE save
        string $type,
        array  $allContentFields,
        bool   $hasSeo,
        string $editRoute,
        array  $editRouteParams = [],
    ): array {
        $sourceLang  = config('app.fallback_locale', 'en');
        $targetLangs = array_values(array_filter(
            supported_languages_keys(),
            fn($l) => $l !== $sourceLang
        ));

        $changedContent = [];
        $unchangedCount = 0;
        foreach ($allContentFields as $field) {
            $newVal = $this->getFieldSourceValue($freshModel, $field, $sourceLang);
            $oldVal = $oldValues[$field] ?? '';
            if (filled($newVal) && $newVal !== $oldVal) {
                $changedContent[] = $field;
            } else {
                $unchangedCount++;
            }
        }

        return [
            'type'            => $type,
            'id'              => $freshModel->id,
            'isUpdate'        => true,
            'translateUrl'    => route('admin.translations.translate'),
            'applyUrl'        => route('admin.translations.apply'),
            'geoGenerateUrl'  => $hasSeo ? route('admin.seo-geo.generate') : null,
            'hasSeo'          => $hasSeo,
            'sourceLang'      => $sourceLang,
            'targetLangs'     => $targetLangs,
            'contentFields'   => $changedContent,
            'editUrl'         => route($editRoute, $editRouteParams),
            'translationsUrl' => route('admin.translations.index', ['type' => $type, 'id' => $freshModel->id]),
            'seoGeoUrl'       => $hasSeo ? route('admin.seo-geo.index', ['type' => $type, 'id' => $freshModel->id]) : null,
            'unchangedCount'  => $unchangedCount,
            'changedFields'   => $changedContent,
        ];
    }

    /**
     * Capture source-lang values for all given fields BEFORE a save,
     * so we can diff afterwards.
     */
    protected function captureSourceValues(
        \Illuminate\Database\Eloquent\Model $model,
        array $fields,
        string $sourceLang,
    ): array {
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $this->getFieldSourceValue($model, $field, $sourceLang);
        }
        return $values;
    }

    /**
     * Extract all translatable text field dot-paths from a description_blocks
     * array (EN source locale).  Returns paths in the form
     * "description_blocks.{blockIndex}.{fieldPath}" ready to be appended to the
     * contentFields list sent to the auto-translate wizard.
     *
     * Mirrors TranslationCheckController::extractBlockTextPaths() but returns
     * plain field strings (no labels) and includes the parent field prefix.
     */
    protected function extractDescriptionBlockFields(array $blocks): array
    {
        $fields = [];

        foreach ($blocks as $blockIdx => $block) {
            $type   = $block['type'] ?? '';
            $prefix = "description_blocks.{$blockIdx}";

            switch ($type) {
                case 'text':
                    if (!empty($block['content'])) {
                        $fields[] = "{$prefix}.content";
                    }
                    break;

                case 'text_column_row':
                    foreach ($block['items'] ?? [] as $itemIdx => $item) {
                        $ip = "{$prefix}.items.{$itemIdx}";
                        if (!empty($item['headline']))  $fields[] = "{$ip}.headline";
                        if (!empty($item['subhead']))   $fields[] = "{$ip}.subhead";
                        if (!empty($item['content']))   $fields[] = "{$ip}.content";
                        if (!empty($item['link_text'])) $fields[] = "{$ip}.link_text";
                    }
                    break;

                case 'floating_gallery':
                    foreach ($block['items'] ?? [] as $itemIdx => $item) {
                        $ip = "{$prefix}.items.{$itemIdx}";
                        if (!empty($item['headline'])) $fields[] = "{$ip}.headline";
                        if (!empty($item['subhead']))  $fields[] = "{$ip}.subhead";
                    }
                    break;

                case 'numbers':
                    if (!empty($block['headline'])) $fields[] = "{$prefix}.headline";
                    foreach ($block['items'] ?? [] as $itemIdx => $item) {
                        $ip = "{$prefix}.items.{$itemIdx}";
                        if (!empty($item['subline'])) $fields[] = "{$ip}.subline";
                        if (!empty($item['title']))   $fields[] = "{$ip}.title";
                        // 'number' is intentionally excluded — it is a layout
                        // value (e.g. "42") preserved via structural sync, not
                        // translated.
                    }
                    break;

                case 'video':
                    if (!empty($block['headline'])) $fields[] = "{$prefix}.headline";
                    if (!empty($block['content']))  $fields[] = "{$prefix}.content";
                    break;
            }
        }

        return $fields;
    }

    /**
     * Read a single field value (supports dot-notation for JSON sub-keys).
     */
    private function getFieldSourceValue(\Illuminate\Database\Eloquent\Model $model, string $field, string $lang): string
    {
        if (str_contains($field, '.')) {
            [$parent, $subKey] = explode('.', $field, 2);
            $val = $model->getTranslation($parent, $lang, false);
            return (string) (is_array($val) ? data_get($val, $subKey) ?? '' : '');
        }
        $val = $model->getTranslation($field, $lang, false);
        if (is_array($val)) return json_encode($val);
        return (string) ($val ?? '');
    }
}
