<?php

namespace App\Http\Requests\Admin\Portfolio\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $rules['hero_image'] = ['nullable', 'image', 'mimes:jpg,png,webp', 'max:20480', 'required_without:hero_image_media_id'];
        $rules['hero_image_media_id'] = ['nullable', 'integer', 'exists:media,id', 'required_without:hero_image'];

        $rules['area'] = ['nullable', 'integer'];
        $rules['slug'] = [
            'nullable',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('projects', 'slug'),
            Rule::unique('project_slug_redirects', 'old_slug'),
        ];
        $rules['sort'] = ['required', 'integer'];
        $rules['active'] = ['required', 'boolean'];
        $rules['inquiry_button_active'] = ['required', 'boolean'];

        $rules['new_files'] = ['nullable', 'array'];
        $rules['new_files.*.name'] = ['nullable', 'string', 'max:255', 'required_with:files.*.file'];
        $rules['new_files.*.file'] = ['nullable', 'file', 'required_with:files.*.name'];

        $rules['gallery'] = ['required', 'array'];
        $rules['gallery.*.image'] = ['required', 'image', 'mimes:jpg,png,webp', 'max:20480'];
        $rules['gallery.*.media_id'] = ['nullable', 'exists:media,id'];

        $rules['title'] = ['required', 'array'];
        $rules['short_description'] = ['required', 'array'];
        $rules['description'] = ['nullable', 'array'];
        $rules['description_blocks'] = ['required', 'array'];
        $rules['location'] = ['required', 'array'];
        $rules['tags'] = ['nullable', 'array'];
        $rules['property_details'] = ['nullable', 'array'];

        $rules['seo_title'] = ['nullable', 'array'];
        $rules['seo_description'] = ['nullable', 'array'];
        $rules['seo_keywords'] = ['nullable', 'array'];
        $rules['geo_text'] = ['nullable', 'array'];

        // Only the source language is required; all other locales are optional
        // (translations are managed via the Translation Dashboard).
        $sourceLang = config('app.fallback_locale', 'en');

        foreach (supported_languages_keys() as $locale) {
            $isSource = $locale === $sourceLang;

            $rules['title.' . $locale] = $isSource ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'];
            $rules['short_description.' . $locale] = $isSource ? ['required', 'string'] : ['nullable', 'string'];
            $rules['description.' . $locale] = ['nullable', 'string'];
            $rules['description_blocks.' . $locale] = $isSource ? ['required', 'array', 'min:1'] : ['nullable', 'array'];
            $rules['description_blocks.' . $locale . '.*.type'] = ['nullable', 'string', 'in:text,floating_gallery,text_column_row,video,embed,numbers'];
            $rules['description_blocks.' . $locale . '.*.content'] = ['nullable', 'string'];
            $rules['description_blocks.' . $locale . '.*.headline'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.headline_col_span'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.headline_line'] = ['nullable'];
            $rules['description_blocks.' . $locale . '.*.grid_col_span'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.grid_col_start'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.items'] = ['nullable', 'array'];
            $rules['description_blocks.' . $locale . '.*.items.*.headline'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.items.*.subhead'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.items.*.title'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.items.*.number'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.items.*.subline'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.items.*.line_color'] = ['nullable', 'string'];
            $rules['description_blocks.' . $locale . '.*.items.*.full_width_line'] = ['nullable'];
            $rules['description_blocks.' . $locale . '.*.items.*.item_col_span'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.items.*.col_span'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.items.*.col_start'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.items.*.image'] = ['nullable', 'string'];
            // Legacy field: pre-refactor `<x-admin.field.image>` rendered `<input type="file" name="image_file">`.
            // The component now emits only `image_file_media_id` (see commit f261af1). Permissive to tolerate
            // stale-form values; controller resolves the media via `image_file_media_id` instead.
            $rules['description_blocks.' . $locale . '.*.items.*.image_file'] = ['nullable'];
            $rules['description_blocks.' . $locale . '.*.items.*.image_file_media_id'] = ['nullable', 'integer', 'exists:media,id'];
            $rules['location.' . $locale] = $isSource ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'];
            $rules['tags.' . $locale . '.*'] = ['nullable', 'string', 'max:255'];
            $rules['property_details.' . $locale . '.*'] = ['nullable', 'string', 'max:255'];

            $rules['seo_title.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['seo_description.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['seo_keywords.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['geo_text.' . $locale] = ['nullable', 'string', 'max:5000'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $blocks    = $this->input('description_blocks', []);
            $sourceLang = config('app.fallback_locale', 'en');

            // Only validate block structure for the source language;
            // other locales are filled via the Translation Dashboard.
            foreach ([$sourceLang] as $locale) {
                $localeBlocks = data_get($blocks, $locale, []);

                foreach ($localeBlocks as $blockIndex => $block) {
                    if (($block['type'] ?? null) === 'text' && blank($block['content'] ?? '')) {
                        $validator->errors()->add(
                            "description_blocks.{$locale}.{$blockIndex}.content",
                            __('validation.required', ['attribute' => 'content'])
                        );
                    }

                    if (($block['type'] ?? null) !== 'floating_gallery') {
                        continue;
                    }

                    $items = $block['items'] ?? [];

                    if (empty($items)) {
                        $validator->errors()->add(
                            "description_blocks.{$locale}.{$blockIndex}.items",
                            __('validation.required', ['attribute' => 'items'])
                        );
                    }

                    foreach ($items as $itemIndex => $item) {
                        $colStart = (int)($item['col_start'] ?? 1);
                        $colSpan = (int)($item['col_span'] ?? 1);
                        $endsAt = $colStart + $colSpan - 1;

                        if ($endsAt > 12) {
                            $validator->errors()->add(
                                "description_blocks.{$locale}.{$blockIndex}.items.{$itemIndex}.col_span",
                                __('validation.max.numeric', ['attribute' => 'col_span', 'max' => 12])
                            );
                        }
                    }
                }
            }
        });
    }
}
