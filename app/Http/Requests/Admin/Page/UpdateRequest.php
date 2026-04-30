<?php

namespace App\Http\Requests\Admin\Page;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $rules['hero_image'] = ['nullable', 'image', 'mimes:jpg,png,webp', 'max:20480'];
        $page = $this->route('page');

        if ($page && in_array($page->slug, static_page_editable_slugs(), true)) {
            $isHome = $page->slug === 'home';
            $rules['public_slug'] = [
                $isHome ? 'nullable' : 'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(static_page_reserved_paths()),
                Rule::unique('pages', 'public_slug')->ignore($page->id),
                Rule::unique('page_slug_redirects', 'old_slug'),
            ];
        }

        $fallback = config('app.fallback_locale');

        foreach (supported_languages_keys() as $locale) {
            $rules['title'] = ['required', 'array'];
            // The admin form only submits the fallback locale; other locales
            // are merged from existing DB translations via preserveTranslations()
            // in the controller. Requiring them here would break every save
            // on a multi-locale install.
            $rules['title.' . $locale] = $locale === $fallback
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];

            $rules['description'] = ['nullable', 'array'];
            $rules['description.' . $locale] = ['nullable', 'string'];

            $rules['seo_title'] = ['nullable', 'array'];
            $rules['seo_title.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['seo_description'] = ['nullable', 'array'];
            $rules['seo_description.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['seo_keywords'] = ['nullable', 'array'];
            $rules['seo_keywords.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['geo_text'] = ['nullable', 'array'];
            $rules['geo_text.' . $locale] = ['nullable', 'string', 'max:5000'];

            $rules['content_data'] = ['nullable', 'array'];
            $rules['content_data.' . $locale] = ['nullable', 'array'];
        }

        return $rules;
    }
}
