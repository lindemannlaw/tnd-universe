<?php

namespace App\Http\Requests\Admin\SiteSections\WhoWeAre;

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
        $rules['back_image'] = ['nullable', 'image', 'mimes:jpg,png,webp', 'max:20480'];
        $rules['back_image_media_id'] = ['nullable', 'integer', 'exists:media,id'];
        $rules['front_image'] = ['nullable', 'image', 'mimes:jpg,png,webp', 'max:20480'];
        $rules['front_image_media_id'] = ['nullable', 'integer', 'exists:media,id'];

        foreach (supported_languages_keys() as $locale) {
            $rules['title'] = ['required', 'array'];
            $rules['title.' . $locale] = ['required', 'string', 'max:255'];

            $rules['content_data'] = ['nullable', 'array'];
            $rules['content_data.' . $locale] = ['nullable', 'array'];
        }

        return $rules;
    }
}
