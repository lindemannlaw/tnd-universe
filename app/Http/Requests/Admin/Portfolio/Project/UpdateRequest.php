<?php

namespace App\Http\Requests\Admin\Portfolio\Project;

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

        $rules['area'] = ['nullable', 'integer'];
        $rules['slug'] = [
            'nullable',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique('projects', 'slug')->ignore($this->route('project')?->id),
            Rule::unique('project_slug_redirects', 'old_slug'),
        ];
        $rules['sort'] = ['required', 'integer'];
        $rules['active'] = ['required', 'boolean'];
        $rules['inquiry_button_active'] = ['required', 'boolean'];

        $rules['current_files'] = ['nullable', 'array'];
        $rules['current_files.*.name'] = ['nullable', 'string', 'max:255'];

        $rules['new_files'] = ['nullable', 'array'];
        $rules['new_files.*.name'] = ['nullable', 'string', 'max:255', 'required_with:files.*.file'];
        $rules['new_files.*.file'] = ['nullable', 'file', 'required_with:files.*.name'];

        $rules['gallery'] = ['required', 'array'];
        $rules['gallery.*.image'] = ['nullable', 'image', 'mimes:jpg,png,webp', 'max:20480'];
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
            $rules['description_blocks.' . $locale . '.*.padding_top'] = ['nullable', 'integer', 'min:0', 'max:300'];
            $rules['description_blocks.' . $locale . '.*.padding_bottom'] = ['nullable', 'integer', 'min:0', 'max:300'];
            // floating_gallery items
            $rules['description_blocks.' . $locale . '.*.items'] = ['nullable', 'array'];
            $rules['description_blocks.' . $locale . '.*.items.*.headline'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.items.*.subhead'] = ['nullable', 'string', 'max:255'];
            // numbers item fields
            $rules['description_blocks.' . $locale . '.*.items.*.title'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.items.*.number'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.items.*.subline'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.items.*.line_color'] = ['nullable', 'string', 'in:emerald-950,emerald-900,emerald-800,primary,gold-bright'];
            $rules['description_blocks.' . $locale . '.*.items.*.full_width_line'] = ['nullable'];
            $rules['description_blocks.' . $locale . '.*.items.*.item_col_span'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.items.*.col_span'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.items.*.col_start'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.items.*.image'] = ['nullable', 'string', 'max:2048'];
            $rules['description_blocks.' . $locale . '.*.items.*.image_file'] = ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'];
            // text_column_row item fields
            $rules['description_blocks.' . $locale . '.*.items.*.headline_color'] = ['nullable', 'string', 'in:emerald-950,emerald-900,emerald-800,primary,gold-bright'];
            $rules['description_blocks.' . $locale . '.*.items.*.headline_font'] = ['nullable', 'string', 'in:pangea,nicevar'];
            $rules['description_blocks.' . $locale . '.*.items.*.headline_line'] = ['nullable'];
            $rules['description_blocks.' . $locale . '.*.items.*.content'] = ['nullable', 'string'];
            $rules['description_blocks.' . $locale . '.*.items.*.content_line'] = ['nullable'];
            $rules['description_blocks.' . $locale . '.*.items.*.link_text'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.items.*.link_url'] = ['nullable', 'string', 'max:500'];
            $rules['description_blocks.' . $locale . '.*.items.*.image_alignment'] = ['nullable', 'string', 'in:top,left,right'];
            $rules['description_blocks.' . $locale . '.*.items.*.image_col_span'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.items.*.text_col_span'] = ['nullable', 'integer', 'min:1', 'max:12'];
            // video block fields
            $rules['description_blocks.' . $locale . '.*.video_source'] = ['nullable', 'string', 'in:upload,url'];
            $rules['description_blocks.' . $locale . '.*.video'] = ['nullable', 'string', 'max:2048'];
            $rules['description_blocks.' . $locale . '.*.video_url'] = ['nullable', 'string', 'max:2048'];
            $rules['description_blocks.' . $locale . '.*.video_file'] = ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:102400'];
            $rules['description_blocks.' . $locale . '.*.col_span'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.col_start'] = ['nullable', 'integer', 'min:1', 'max:12'];
            // embed block fields
            $rules['description_blocks.' . $locale . '.*.embed_url'] = ['nullable', 'string', 'max:2048'];
            $rules['description_blocks.' . $locale . '.*.embed_height'] = ['nullable', 'integer', 'min:100', 'max:2000'];
            // block-level headline/content (used by video block)
            $rules['description_blocks.' . $locale . '.*.headline'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.headline_color'] = ['nullable', 'string', 'in:emerald-950,emerald-900,emerald-800,primary,gold-bright'];
            $rules['description_blocks.' . $locale . '.*.headline_font'] = ['nullable', 'string', 'in:pangea,nicevar'];
            $rules['description_blocks.' . $locale . '.*.headline_line'] = ['nullable'];
            $rules['description_blocks.' . $locale . '.*.content_line'] = ['nullable'];
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
            $blocks     = $this->input('description_blocks', []);
            $sourceLang = config('app.fallback_locale', 'en');

            // Only validate block structure for the source language.
            foreach ([$sourceLang] as $locale) {
                $localeBlocks = data_get($blocks, $locale, []);

                foreach ($localeBlocks as $blockIndex => $block) {
                    if (($block['type'] ?? null) === 'text' && blank($block['content'] ?? '')) {
                        $validator->errors()->add(
                            "description_blocks.{$locale}.{$blockIndex}.content",
                            __('validation.required', ['attribute' => 'content'])
                        );
                    }

                    $blockType = $block['type'] ?? null;

                    if (!in_array($blockType, ['floating_gallery', 'text_column_row'])) {
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
