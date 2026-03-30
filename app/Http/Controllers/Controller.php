<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * When saving a model from an EN-only form, merge the submitted locale
     * values with the existing translations so DE/FR/PL etc. are preserved.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $data  validated request data (modified in-place)
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
}
