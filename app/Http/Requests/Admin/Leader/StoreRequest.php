<?php

namespace App\Http\Requests\Admin\Leader;

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

        $rules['photo'] = ['required', 'image', 'mimes:jpg,png,webp', 'max:20480'];
        $rules['resume'] = ['required', 'file', 'max:20480'];

        $rules['sort'] = ['required', 'integer'];
        $rules['active'] = ['required', 'boolean'];

        $rules['name'] = ['required', 'array'];
        $rules['position'] = ['required', 'array'];
        $rules['info'] = ['nullable', 'array'];

        foreach (supported_languages_keys() as $locale) {
            $isSource = $locale === $sourceLang;

            $rules['name.' . $locale] = [$isSource ? 'required' : 'nullable', 'string', 'max:255'];
            $rules['position.' . $locale] = [$isSource ? 'required' : 'nullable', 'string', 'max:255'];

            $rules['info.' . $locale] = ['nullable', 'array'];
            $rules['info.' . $locale . '*.head'] = ['nullable', 'string', 'max:255'];
            $rules['info.' . $locale . '*.description'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
