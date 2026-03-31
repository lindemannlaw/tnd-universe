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
     * All content & SEO fields are included.
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
            'seoFields'      => $hasSeo ? ['seo_title', 'seo_description', 'seo_keywords', 'geo_text'] : [],
            'editUrl'        => route($editRoute, $editRouteParams),
            'translationsUrl' => route('admin.translations.index', ['type' => $type, 'id' => $model->id]),
            'seoGeoUrl'      => $hasSeo ? route('admin.seo-geo.index', ['type' => $type, 'id' => $model->id]) : null,
            'unchangedCount' => 0,
            'changedFields'  => $contentFields,
            'changedSeoFields' => $hasSeo ? ['seo_title', 'seo_description', 'seo_keywords', 'geo_text'] : [],
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

        // SEO fields: include if any changed
        $changedSeo = [];
        if ($hasSeo) {
            foreach (['seo_title', 'seo_description', 'seo_keywords', 'geo_text'] as $sf) {
                $newVal = (string) ($freshModel->getTranslation($sf, $sourceLang, false) ?? '');
                $oldVal = $oldValues[$sf] ?? '';
                if (filled($newVal) && $newVal !== $oldVal) {
                    $changedSeo[] = $sf;
                }
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
            'seoFields'       => $changedSeo,
            'editUrl'         => route($editRoute, $editRouteParams),
            'translationsUrl' => route('admin.translations.index', ['type' => $type, 'id' => $freshModel->id]),
            'seoGeoUrl'       => $hasSeo ? route('admin.seo-geo.index', ['type' => $type, 'id' => $freshModel->id]) : null,
            'unchangedCount'  => $unchangedCount,
            'changedFields'   => $changedContent,
            'changedSeoFields' => $changedSeo,
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
