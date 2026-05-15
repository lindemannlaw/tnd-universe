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

        $rules['photo'] = ['nullable', 'image', 'mimes:jpg,png,webp', 'max:20480', 'required_without:photo_media_id'];
        $rules['photo_media_id'] = ['nullable', 'integer', 'exists:media,id', 'required_without:photo'];
        $rules['resume'] = ['nullable', 'file', 'max:20480', 'required_without:resume_media_id'];
        $rules['resume_media_id'] = ['nullable', 'integer', 'exists:media,id', 'required_without:resume'];

        $rules['sort'] = ['required', 'integer'];
        $rules['active'] = ['required', 'boolean'];

        $rules['name'] = ['required', 'array'];
        $rules['position'] = ['required', 'array'];
        $rules['subtitle'] = ['nullable', 'array'];
        $rules['info'] = ['nullable', 'array'];

        foreach (supported_languages_keys() as $locale) {
            $isSource = $locale === $sourceLang;

            $rules['name.' . $locale] = [$isSource ? 'required' : 'nullable', 'string', 'max:255'];
            $rules['position.' . $locale] = [$isSource ? 'required' : 'nullable', 'string', 'max:255'];
            $rules['subtitle.' . $locale] = ['nullable', 'string', 'max:255'];

            $rules['info.' . $locale] = ['nullable', 'array'];
            $rules['info.' . $locale . '*.head'] = ['nullable', 'string', 'max:255'];
            $rules['info.' . $locale . '*.description'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
