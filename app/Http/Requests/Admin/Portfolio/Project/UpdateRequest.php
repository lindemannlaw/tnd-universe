<?php

namespace App\Http\Requests\Admin\Portfolio\Project;

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
        $rules['hero_image'] = ['nullable', 'image', 'mimes:jpg,png,webp', 'max:20480'];

        $rules['area'] = ['nullable', 'integer'];
        $rules['sort'] = ['required', 'integer'];
        $rules['active'] = ['required', 'boolean'];

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

        foreach (supported_languages_keys() as $locale) {
            $rules['title.' . $locale] = ['required', 'string', 'max:255'];
            $rules['short_description.' . $locale] = ['required', 'string'];
            $rules['description.' . $locale] = ['nullable', 'string'];
            $rules['description_blocks.' . $locale] = ['required', 'array', 'min:1'];
            $rules['description_blocks.' . $locale . '.*.type'] = ['required', 'string', 'in:text,floating_gallery,text_column'];
            $rules['description_blocks.' . $locale . '.*.content'] = ['nullable', 'string'];
            $rules['description_blocks.' . $locale . '.*.items'] = ['nullable', 'array'];
            $rules['description_blocks.' . $locale . '.*.items.*.headline'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.items.*.subhead'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.items.*.col_span'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.items.*.col_start'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.items.*.image'] = ['nullable', 'string'];
            $rules['description_blocks.' . $locale . '.*.items.*.image_file'] = ['nullable', 'image', 'mimes:jpg,png,webp', 'max:20480'];
            $rules['description_blocks.' . $locale . '.*.headline'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.headline_color'] = ['nullable', 'string', 'in:emerald-950,emerald-900,emerald-800,primary,gold-bright'];
            $rules['description_blocks.' . $locale . '.*.headline_font'] = ['nullable', 'string', 'in:pangea,nicevar'];
            $rules['description_blocks.' . $locale . '.*.padding_top'] = ['nullable', 'integer', 'min:0', 'max:300'];
            $rules['description_blocks.' . $locale . '.*.padding_bottom'] = ['nullable', 'integer', 'min:0', 'max:300'];
            $rules['description_blocks.' . $locale . '.*.image_file'] = ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'];
            $rules['description_blocks.' . $locale . '.*.image_alignment'] = ['nullable', 'string', 'in:top,left,right'];
            $rules['description_blocks.' . $locale . '.*.image_col_span'] = ['nullable', 'integer', 'min:1', 'max:11'];
            $rules['description_blocks.' . $locale . '.*.headline_line'] = ['nullable'];
            $rules['description_blocks.' . $locale . '.*.content_line'] = ['nullable'];
            $rules['description_blocks.' . $locale . '.*.link_text'] = ['nullable', 'string', 'max:255'];
            $rules['description_blocks.' . $locale . '.*.link_url'] = ['nullable', 'string', 'max:500'];
            $rules['description_blocks.' . $locale . '.*.col_span'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['description_blocks.' . $locale . '.*.col_start'] = ['nullable', 'integer', 'min:1', 'max:12'];
            $rules['location.' . $locale] = ['required', 'string', 'max:255'];
            $rules['tags.' . $locale . '.*'] = ['nullable', 'string', 'max:255'];
            $rules['property_details.' . $locale . '.*'] = ['nullable', 'string', 'max:255'];

            $rules['seo_title.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['seo_description.' . $locale] = ['nullable', 'string', 'max:255'];
            $rules['seo_keywords.' . $locale] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $blocks = $this->input('description_blocks', []);

            foreach (supported_languages_keys() as $locale) {
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
