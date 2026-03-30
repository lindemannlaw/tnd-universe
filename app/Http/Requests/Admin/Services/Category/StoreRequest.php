<?php

namespace App\Http\Requests\Admin\Services\Category;

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

        $rules['hero_image'] = ['required', 'image', 'mimes:jpg,png,webp', 'max:20480'];

        $rules['sort'] = ['required', 'integer'];
        $rules['active'] = ['required', 'boolean'];

        $rules['name'] = ['required', 'array'];

        foreach (supported_languages_keys() as $locale) {
            $isSource = $locale === $sourceLang;
            $rules['name.' . $locale] = [$isSource ? 'required' : 'nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
