<?php

namespace App\Http\Requests\Admin\Services\Service;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $sourceLang = config('app.fallback_locale', 'en');

        $rules['hero_image'] = ['nullable', 'image', 'mimes:jpg,png,webp', 'max:20480'];
        $rules['hero_image_media_id'] = ['nullable', 'integer', 'exists:media,id'];
        $rules['info_image'] = ['nullable', 'image', 'mimes:jpg,png,webp', 'max:20480'];
        $rules['info_image_media_id'] = ['nullable', 'integer', 'exists:media,id'];

        $rules['service_category_id'] = ['required', 'integer', 'exists:service_categories,id'];

        $rules['sort'] = ['required', 'integer'];
        $rules['active'] = ['required', 'boolean'];

        $rules['title'] = ['required', 'array'];
        $rules['inner_title'] = ['nullable', 'array'];
        $rules['description'] = ['required', 'array'];
        $rules['details'] = ['required', 'array'];

        $rules['seo_title'] = ['nullable', 'array'];
        $rules['seo_description'] = ['nullable', 'array'];
        $rules['seo_keywords'] = ['nullable', 'array'];
        $rules['geo_text'] = ['nullable', 'array'];

        foreach (supported_languages_keys() as $locale) {
            $isSource = $locale === $sourceLang;

            $rules['title.' . $locale] = [$isSource ? 'required' : 'nullable', 'string', 'max:255'];
            $rules['inner_title.' . $locale] = ['nullable', 'string', 'max:255'];

            $rules['description.' . $locale] = [$isSource ? 'required' : 'nullable', 'string'];

            $rules['details.' . $locale . '.title'] = [$isSource ? 'required' : 'nullable', 'string', 'max:255'];
            $rules['details.' . $locale . '.list'] = [$isSource ? 'required' : 'nullable', 'array'];
            $rules['details.' . $locale . '.list.*.title'] = [$isSource ? 'required' : 'nullable', 'string', 'max:255'];
            $rules['details.' . $locale . '.list.*.description'] = [$isSource ? 'required' : 'nullable', 'string'];

            $rules['seo_title.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['seo_description.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['seo_keywords.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['geo_text.' . $locale] = ['nullable', 'string', 'max:5000'];
        }

        return $rules;
    }
}
