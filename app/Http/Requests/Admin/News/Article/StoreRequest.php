<?php

namespace App\Http\Requests\Admin\News\Article;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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

        $rules['sort'] = ['required', 'integer'];
        $rules['active'] = ['required', 'boolean'];
        $rules['category_id'] = ['required', 'exists:news_categories,id'];

        $rules['title'] = ['required', 'array'];
        $rules['short_description'] = ['nullable', 'array'];
        $rules['description'] = ['required', 'array'];
        $rules['seo_title'] = ['nullable', 'array'];
        $rules['seo_description'] = ['nullable', 'array'];
        $rules['seo_keywords'] = ['nullable', 'array'];
        $rules['geo_text'] = ['nullable', 'array'];

        $rules['link_top_active'] = ['required', 'boolean'];
        $rules['link_top_text'] = ['nullable', 'array'];
        $rules['link_top_url'] = ['nullable', 'string', 'max:2048'];
        $rules['link_top_file'] = ['nullable', 'file', 'max:51200'];

        $rules['link_bottom_active'] = ['required', 'boolean'];
        $rules['link_bottom_text'] = ['nullable', 'array'];
        $rules['link_bottom_url'] = ['nullable', 'string', 'max:2048'];
        $rules['link_bottom_file'] = ['nullable', 'file', 'max:51200'];

        foreach (supported_languages_keys() as $locale) {
            $isSource = $locale === $sourceLang;

            $rules['title.' . $locale] = [$isSource ? 'required' : 'nullable', 'string', 'max:255'];
            $rules['short_description.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['description.' . $locale] = [$isSource ? 'required' : 'nullable', 'string'];

            $rules['seo_title.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['seo_description.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['seo_keywords.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['geo_text.' . $locale] = ['nullable', 'string', 'max:5000'];

            $rules['link_top_text.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['link_bottom_text.' . $locale] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
