<x-admin.tabs.wrapper>
    <x-slot:nav>
        @foreach(supported_languages_keys() as $lang)
            <x-admin.tabs.nav-item
                :is-active="$loop->first"
                :target="'description-locale-' . $lang"
                :title="$lang"
            />
        @endforeach
    </x-slot:nav>

    <x-slot:content>
        @foreach(supported_languages_keys() as $lang)
            <x-admin.tabs.pane :is-active="$loop->first" :id="'description-locale-' . $lang">
                @php
                    $fallbackTextBlock = [
                        'type' => 'text',
                        'content' => old('description.' . $lang, isset($project) ? $project->getTranslation('description', $lang) : null),
                    ];

                    $blocks = old(
                        'description_blocks.' . $lang,
                        isset($project)
                            ? ($project->getTranslation('description_blocks', $lang) ?: [$fallbackTextBlock])
                            : [$fallbackTextBlock]
                    );

                    // Migrate legacy flat text_column blocks → text_column_row with single item
                    $blocks = array_map(function ($b) {
                        if (($b['type'] ?? null) === 'text_column') {
                            $item = $b;
                            unset($item['type'], $item['padding_top'], $item['padding_bottom']);
                            return [
                                'type'          => 'text_column_row',
                                'padding_top'   => $b['padding_top'] ?? 0,
                                'padding_bottom'=> $b['padding_bottom'] ?? 0,
                                'items'         => [$item],
                            ];
                        }
                        return $b;
                    }, $blocks);
                @endphp

                <div
                    class="d-flex flex-column gap-4"
                    data-project-description-builder
                    data-locale="{{ $lang }}"
                >
                    <div class="d-flex flex-column gap-4" data-blocks-wrapper>
                        @foreach($blocks as $blockIndex => $block)
                            <div class="border rounded p-3 d-flex flex-column gap-3 bg-white" data-block>
                                <input type="hidden" name="description_blocks[{{ $lang }}][{{ $blockIndex }}][type]" value="{{ data_get($block, 'type', 'text') }}" data-block-type-input>

                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold me-1" data-block-label>Block</span>
                                    <select class="form-select w-auto" data-block-type-select>
                                        <option value="text" {{ data_get($block, 'type', 'text') === 'text' ? 'selected' : null }}>Text</option>
                                        <option value="floating_gallery" {{ data_get($block, 'type') === 'floating_gallery' ? 'selected' : null }}>Floating Image Gallery</option>
                                        <option value="text_column_row" {{ data_get($block, 'type') === 'text_column_row' ? 'selected' : null }}>Text Column Row</option>
                                    </select>
                                    <x-admin.button data-block-add-after class="p-2 ms-auto" :btn="'btn-outline-success'" :iconName="'plus-circle'" />
                                    <x-admin.button data-block-remove class="p-2" :btn="'btn-outline-danger'" :iconName="'dash-circle'" />
                                    <x-admin.button data-block-move class="p-2" :btn="'btn-outline-secondary'" :iconName="'arrows-move'" />
                                    <button type="button" class="btn btn-outline-secondary p-2" data-block-toggle aria-expanded="false">
                                        <span data-block-toggle-icon>></span>
                                    </button>
                                </div>

                                <div class="d-flex flex-column gap-3 d-none" data-block-body>

                                    {{-- TEXT --}}
                                    <div data-block-type-panel="text" class="{{ data_get($block, 'type', 'text') === 'text' ? null : 'd-none' }}">
                                        <x-admin.field.wysiwyg
                                            :name="'description_blocks['. $lang .'][' . $blockIndex . '][content]'"
                                            :placeholder="__('admin.description')"
                                            :value="data_get($block, 'content')"
                                            :height="300"
                                            :buttons="'blockquote|list|image|video'"
                                        />
                                    </div>

                                    {{-- TEXT COLUMN ROW --}}
                                    <div data-block-type-panel="text_column_row" class="d-flex flex-column gap-3 {{ data_get($block, 'type') === 'text_column_row' ? null : 'd-none' }}">

                                        {{-- Row padding --}}
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <x-admin.field.padding-select
                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][padding_top]'"
                                                    :value="data_get($block, 'padding_top', 0)"
                                                    :placeholder="'Padding oben'"
                                                />
                                            </div>
                                            <div class="col-6">
                                                <x-admin.field.padding-select
                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][padding_bottom]'"
                                                    :value="data_get($block, 'padding_bottom', 0)"
                                                    :placeholder="'Padding unten'"
                                                />
                                            </div>
                                        </div>

                                        {{-- Column items --}}
                                        <div class="d-flex flex-column gap-4" data-tc-items-wrapper>
                                            @foreach((data_get($block, 'items') ?: []) as $itemIndex => $item)
                                                <div class="border rounded p-3 d-flex flex-column gap-3 bg-light" data-tc-item>

                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="fw-semibold small text-muted">Spalte {{ $itemIndex + 1 }}</span>
                                                        <x-admin.button data-tc-item-move class="p-2 ms-auto" :btn="'btn-outline-secondary'" :iconName="'arrows-move'" />
                                                        <x-admin.button data-tc-item-add-after class="p-2" :btn="'btn-outline-success'" :iconName="'plus-circle'" />
                                                        <x-admin.button data-tc-item-remove class="p-2" :btn="'btn-outline-danger'" :iconName="'dash-circle'" />
                                                    </div>

                                                    {{-- Position --}}
                                                    <div>
                                                        <p class="text-muted small mb-2 fw-semibold">Position</p>
                                                        <div class="row g-2">
                                                            <div class="col-6">
                                                                <x-admin.field.number
                                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][col_span]'"
                                                                    :value="data_get($item, 'col_span', 12)"
                                                                    :placeholder="'Anzahl Spalten (1-12)'"
                                                                    :fieldAttrs="'min=1 max=12'"
                                                                />
                                                            </div>
                                                            <div class="col-6">
                                                                <x-admin.field.number
                                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][col_start]'"
                                                                    :value="data_get($item, 'col_start', 1)"
                                                                    :placeholder="'Start Spalte (1-12)'"
                                                                    :fieldAttrs="'min=1 max=12'"
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Bild --}}
                                                    <div class="border-top pt-3">
                                                        <p class="text-muted small mb-2 fw-semibold">Bild (optional)</p>
                                                        <div class="d-flex flex-column gap-2">
                                                            <div>
                                                                <input type="hidden"
                                                                    name="description_blocks[{{ $lang }}][{{ $blockIndex }}][items][{{ $itemIndex }}][image]"
                                                                    value="{{ data_get($item, 'image') }}"
                                                                    data-tc-item-image-hidden
                                                                >
                                                                <x-admin.field.image
                                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][image_file]'"
                                                                    :src="data_get($item, 'image')"
                                                                    :required="false"
                                                                    :compact="true"
                                                                    :placeholder="'Bild hinzufügen'"
                                                                />
                                                            </div>
                                                            <div class="row g-2">
                                                                <div class="col-4">
                                                                    <label class="form-label small">Alignment</label>
                                                                    <select class="form-select form-select-sm" name="description_blocks[{{ $lang }}][{{ $blockIndex }}][items][{{ $itemIndex }}][image_alignment]">
                                                                        <option value="top" {{ data_get($item, 'image_alignment', 'top') === 'top' ? 'selected' : '' }}>Oben</option>
                                                                        <option value="left" {{ data_get($item, 'image_alignment') === 'left' ? 'selected' : '' }}>Links</option>
                                                                        <option value="right" {{ data_get($item, 'image_alignment') === 'right' ? 'selected' : '' }}>Rechts</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-4">
                                                                    <x-admin.field.number
                                                                        :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][image_col_span]'"
                                                                        :value="data_get($item, 'image_col_span')"
                                                                        :placeholder="'Bild Spalten'"
                                                                        :required="false"
                                                                        :fieldAttrs="'min=1 max=12'"
                                                                    />
                                                                </div>
                                                                <div class="col-4">
                                                                    <x-admin.field.number
                                                                        :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][text_col_span]'"
                                                                        :value="data_get($item, 'text_col_span')"
                                                                        :placeholder="'Text Spalten'"
                                                                        :required="false"
                                                                        :fieldAttrs="'min=1 max=12'"
                                                                    />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Text --}}
                                                    <div class="border-top pt-3">
                                                        <p class="text-muted small mb-2 fw-semibold">Text</p>
                                                        <div class="row g-2">
                                                            <div class="col-12">
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox" value="1"
                                                                        name="description_blocks[{{ $lang }}][{{ $blockIndex }}][items][{{ $itemIndex }}][headline_line]"
                                                                        id="hl_line_{{ $lang }}_{{ $blockIndex }}_{{ $itemIndex }}"
                                                                        {{ data_get($item, 'headline_line') ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="hl_line_{{ $lang }}_{{ $blockIndex }}_{{ $itemIndex }}">Linie vor Headline</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-lg-6">
                                                                <x-admin.field.text
                                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][headline]'"
                                                                    :value="data_get($item, 'headline')"
                                                                    :required="false"
                                                                    :placeholder="'Headline (optional)'"
                                                                />
                                                            </div>
                                                            <div class="col-6 col-lg-4">
                                                                <select class="form-select form-select-sm" name="description_blocks[{{ $lang }}][{{ $blockIndex }}][items][{{ $itemIndex }}][headline_color]">
                                                                    @foreach(['primary' => 'Primary (Dark)', 'emerald-950' => 'Emerald 950', 'emerald-900' => 'Emerald 900', 'emerald-800' => 'Emerald 800', 'gold-bright' => 'Gold Bright'] as $val => $label)
                                                                        <option value="{{ $val }}" {{ data_get($item, 'headline_color', 'primary') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-6 col-lg-2 d-flex align-items-center">
                                                                <div class="form-check form-switch mb-0">
                                                                    <input class="form-check-input" type="checkbox" value="nicevar"
                                                                        name="description_blocks[{{ $lang }}][{{ $blockIndex }}][items][{{ $itemIndex }}][headline_font]"
                                                                        id="hf_{{ $lang }}_{{ $blockIndex }}_{{ $itemIndex }}"
                                                                        {{ data_get($item, 'headline_font') === 'nicevar' ? 'checked' : '' }}>
                                                                    <label class="form-check-label small" for="hf_{{ $lang }}_{{ $blockIndex }}_{{ $itemIndex }}">NiceVar</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-12">
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox" value="1"
                                                                        name="description_blocks[{{ $lang }}][{{ $blockIndex }}][items][{{ $itemIndex }}][content_line]"
                                                                        id="content_line_{{ $lang }}_{{ $blockIndex }}_{{ $itemIndex }}"
                                                                        {{ data_get($item, 'content_line') ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="content_line_{{ $lang }}_{{ $blockIndex }}_{{ $itemIndex }}">Linie vor Text</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-12">
                                                                <x-admin.field.wysiwyg
                                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][content]'"
                                                                    :placeholder="'Text (optional)'"
                                                                    :value="data_get($item, 'content')"
                                                                    :height="200"
                                                                    :buttons="'bold|italic|link'"
                                                                />
                                                            </div>
                                                            <div class="col-12 col-lg-6">
                                                                <x-admin.field.text
                                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][link_text]'"
                                                                    :value="data_get($item, 'link_text')"
                                                                    :required="false"
                                                                    :placeholder="'Link Text (optional)'"
                                                                />
                                                            </div>
                                                            <div class="col-12 col-lg-6">
                                                                <x-admin.field.text
                                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][link_url]'"
                                                                    :value="data_get($item, 'link_url')"
                                                                    :required="false"
                                                                    :placeholder="'Link URL (optional)'"
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            @endforeach
                                        </div>

                                        <x-admin.button
                                            data-tc-item-add
                                            class="ms-auto"
                                            :btn="'btn-outline-primary'"
                                            :title="'Spalte hinzufügen'"
                                            :iconName="'plus-circle'"
                                        />

                                    </div>

                                    {{-- FLOATING GALLERY --}}
                                    <div data-block-type-panel="floating_gallery" class="d-flex flex-column gap-3 {{ data_get($block, 'type') === 'floating_gallery' ? null : 'd-none' }}">
                                        <div class="d-flex flex-column gap-3" data-gallery-items-wrapper>
                                            @foreach((data_get($block, 'items') ?: []) as $itemIndex => $item)
                                                <div class="border rounded p-3 d-flex flex-column gap-3" data-gallery-item>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <x-admin.button data-gallery-item-move class="p-2" :btn="'btn-outline-secondary'" :iconName="'arrows-move'" />
                                                        <x-admin.button data-gallery-item-add-after class="p-2 ms-auto" :btn="'btn-outline-success'" :iconName="'plus-circle'" />
                                                        <x-admin.button data-gallery-item-remove class="p-2" :btn="'btn-outline-danger'" :iconName="'dash-circle'" />
                                                    </div>
                                                    <x-admin.field.image
                                                        :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][image_file]'"
                                                        :placeholder="'Bild hinzufügen'"
                                                        :src="data_get($item, 'image')"
                                                        :required="false"
                                                        :compact="true"
                                                    />
                                                    <input type="hidden" name="description_blocks[{{ $lang }}][{{ $blockIndex }}][items][{{ $itemIndex }}][image]" value="{{ data_get($item, 'image') }}" data-gallery-item-image-hidden>
                                                    <div class="row g-2">
                                                        <div class="col-12 col-lg-6">
                                                            <x-admin.field.text :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][headline]'" :value="data_get($item, 'headline')" :required="false" :placeholder="'Headline (optional)'" />
                                                        </div>
                                                        <div class="col-12 col-lg-6">
                                                            <x-admin.field.text :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][subhead]'" :value="data_get($item, 'subhead')" :required="false" :placeholder="'Subhead (optional)'" />
                                                        </div>
                                                        <div class="col-12 col-lg-6">
                                                            <x-admin.field.number :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][col_span]'" :value="data_get($item, 'col_span', 12)" :placeholder="'Columns (1-12)'" :fieldAttrs="'min=1 max=12'" />
                                                        </div>
                                                        <div class="col-12 col-lg-6">
                                                            <x-admin.field.number :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][col_start]'" :value="data_get($item, 'col_start', 1)" :placeholder="'Start column (1-12)'" :fieldAttrs="'min=1 max=12'" />
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <x-admin.button data-gallery-item-add class="ms-auto" :btn="'btn-outline-primary'" :title="'Add image'" :iconName="'plus-circle'" />
                                        <div class="row g-2 mt-1">
                                            <div class="col-6">
                                                <x-admin.field.padding-select
                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][padding_top]'"
                                                    :value="data_get($block, 'padding_top', 0)"
                                                    :placeholder="'Padding oben'"
                                                />
                                            </div>
                                            <div class="col-6">
                                                <x-admin.field.padding-select
                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][padding_bottom]'"
                                                    :value="data_get($block, 'padding_bottom', 0)"
                                                    :placeholder="'Padding unten'"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    </div>

                    <x-admin.button data-block-add class="ms-auto" :btn="'btn-outline-primary'" :title="'Add block'" :iconName="'plus-circle'" />

                    @if($lang === 'en')
                        <hr class="my-1">
                        <x-admin.button
                            data-translate-blocks
                            data-target-locale="de"
                            data-translate-url="{{ route('admin.translate') }}"
                            class="ms-auto btn-sm"
                            :btn="'btn-outline-info'"
                            :iconName="'globe'"
                            :title="'Auf Deutsch übersetzen'"
                        />
                    @endif
                </div>

                {{-- Block template --}}
                <template data-block-template="text">
                    <div class="border rounded p-3 d-flex flex-column gap-3 bg-white" data-block>
                        <input type="hidden" name="description_blocks[{{ $lang }}][__block__][type]" value="text" data-block-type-input>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-semibold me-1" data-block-label>Block</span>
                            <select class="form-select w-auto" data-block-type-select>
                                <option value="text" selected>Text</option>
                                <option value="floating_gallery">Floating Image Gallery</option>
                                <option value="text_column_row">Text Column Row</option>
                            </select>
                            <x-admin.button data-block-add-after class="p-2 ms-auto" :btn="'btn-outline-success'" :iconName="'plus-circle'" />
                            <x-admin.button data-block-remove class="p-2" :btn="'btn-outline-danger'" :iconName="'dash-circle'" />
                            <x-admin.button data-block-move class="p-2" :btn="'btn-outline-secondary'" :iconName="'arrows-move'" />
                            <button type="button" class="btn btn-outline-secondary p-2" data-block-toggle aria-expanded="false">
                                <span data-block-toggle-icon>></span>
                            </button>
                        </div>
                        <div class="d-flex flex-column gap-3 d-none" data-block-body>
                            <div data-block-type-panel="text">
                                <x-admin.field.wysiwyg :name="'description_blocks['. $lang .'][__block__][content]'" :placeholder="__('admin.description')" :height="300" :buttons="'blockquote|list|image|video'" />
                            </div>
                            <div data-block-type-panel="text_column_row" class="d-flex flex-column gap-3 d-none">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <x-admin.field.padding-select :name="'description_blocks['. $lang .'][__block__][padding_top]'" :value="0" :placeholder="'Padding oben'" />
                                    </div>
                                    <div class="col-6">
                                        <x-admin.field.padding-select :name="'description_blocks['. $lang .'][__block__][padding_bottom]'" :value="0" :placeholder="'Padding unten'" />
                                    </div>
                                </div>
                                <div class="d-flex flex-column gap-4" data-tc-items-wrapper></div>
                                <x-admin.button data-tc-item-add class="ms-auto" :btn="'btn-outline-primary'" :title="'Spalte hinzufügen'" :iconName="'plus-circle'" />
                            </div>
                            <div data-block-type-panel="floating_gallery" class="d-flex flex-column gap-3 d-none">
                                <div class="d-flex flex-column gap-3" data-gallery-items-wrapper></div>
                                <x-admin.button data-gallery-item-add class="ms-auto" :btn="'btn-outline-primary'" :title="'Add image'" :iconName="'plus-circle'" />
                                <div class="row g-2 mt-1">
                                    <div class="col-6">
                                        <x-admin.field.padding-select :name="'description_blocks['. $lang .'][__block__][padding_top]'" :value="0" :placeholder="'Padding oben'" />
                                    </div>
                                    <div class="col-6">
                                        <x-admin.field.padding-select :name="'description_blocks['. $lang .'][__block__][padding_bottom]'" :value="0" :placeholder="'Padding unten'" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Gallery item template --}}
                <template data-gallery-item-template>
                    <div class="border rounded p-3 d-flex flex-column gap-3" data-gallery-item>
                        <div class="d-flex align-items-center gap-2">
                            <x-admin.button data-gallery-item-move class="p-2" :btn="'btn-outline-secondary'" :iconName="'arrows-move'" />
                            <x-admin.button data-gallery-item-add-after class="p-2 ms-auto" :btn="'btn-outline-success'" :iconName="'plus-circle'" />
                            <x-admin.button data-gallery-item-remove class="p-2" :btn="'btn-outline-danger'" :iconName="'dash-circle'" />
                        </div>
                        <x-admin.field.image :name="'description_blocks['. $lang .'][__block__][items][__item__][image_file]'" :placeholder="'Bild hinzufügen'" :required="false" :compact="true" />
                        <input type="hidden" name="description_blocks[{{ $lang }}][__block__][items][__item__][image]" value="" data-gallery-item-image-hidden>
                        <div class="row g-2">
                            <div class="col-12 col-lg-6">
                                <x-admin.field.text :name="'description_blocks['. $lang .'][__block__][items][__item__][headline]'" :required="false" :placeholder="'Headline (optional)'" />
                            </div>
                            <div class="col-12 col-lg-6">
                                <x-admin.field.text :name="'description_blocks['. $lang .'][__block__][items][__item__][subhead]'" :required="false" :placeholder="'Subhead (optional)'" />
                            </div>
                            <div class="col-12 col-lg-6">
                                <x-admin.field.number :name="'description_blocks['. $lang .'][__block__][items][__item__][col_span]'" :value="12" :placeholder="'Columns (1-12)'" :fieldAttrs="'min=1 max=12'" />
                            </div>
                            <div class="col-12 col-lg-6">
                                <x-admin.field.number :name="'description_blocks['. $lang .'][__block__][items][__item__][col_start]'" :value="1" :placeholder="'Start column (1-12)'" :fieldAttrs="'min=1 max=12'" />
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Text column item template --}}
                <template data-tc-item-template>
                    <div class="border rounded p-3 d-flex flex-column gap-3 bg-light" data-tc-item>

                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-semibold small text-muted">Spalte</span>
                            <x-admin.button data-tc-item-move class="p-2 ms-auto" :btn="'btn-outline-secondary'" :iconName="'arrows-move'" />
                            <x-admin.button data-tc-item-add-after class="p-2" :btn="'btn-outline-success'" :iconName="'plus-circle'" />
                            <x-admin.button data-tc-item-remove class="p-2" :btn="'btn-outline-danger'" :iconName="'dash-circle'" />
                        </div>

                        {{-- Position --}}
                        <div>
                            <p class="text-muted small mb-2 fw-semibold">Position</p>
                            <div class="row g-2">
                                <div class="col-6">
                                    <x-admin.field.number :name="'description_blocks['. $lang .'][__block__][items][__item__][col_span]'" :value="12" :placeholder="'Anzahl Spalten (1-12)'" :fieldAttrs="'min=1 max=12'" />
                                </div>
                                <div class="col-6">
                                    <x-admin.field.number :name="'description_blocks['. $lang .'][__block__][items][__item__][col_start]'" :value="1" :placeholder="'Start Spalte (1-12)'" :fieldAttrs="'min=1 max=12'" />
                                </div>
                            </div>
                        </div>

                        {{-- Bild --}}
                        <div class="border-top pt-3">
                            <p class="text-muted small mb-2 fw-semibold">Bild (optional)</p>
                            <div class="d-flex flex-column gap-2">
                                <div>
                                    <input type="hidden" name="description_blocks[{{ $lang }}][__block__][items][__item__][image]" value="" data-tc-item-image-hidden>
                                    <x-admin.field.image
                                        :name="'description_blocks['. $lang .'][__block__][items][__item__][image_file]'"
                                        :required="false"
                                        :compact="true"
                                        :placeholder="'Bild hinzufügen'"
                                    />
                                </div>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label small">Alignment</label>
                                        <select class="form-select form-select-sm" name="description_blocks[{{ $lang }}][__block__][items][__item__][image_alignment]">
                                            <option value="top" selected>Oben</option>
                                            <option value="left">Links</option>
                                            <option value="right">Rechts</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <x-admin.field.number :name="'description_blocks['. $lang .'][__block__][items][__item__][image_col_span]'" :placeholder="'Bild Spalten'" :required="false" :fieldAttrs="'min=1 max=12'" />
                                    </div>
                                    <div class="col-4">
                                        <x-admin.field.number :name="'description_blocks['. $lang .'][__block__][items][__item__][text_col_span]'" :placeholder="'Text Spalten'" :required="false" :fieldAttrs="'min=1 max=12'" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Text --}}
                        <div class="border-top pt-3">
                            <p class="text-muted small mb-2 fw-semibold">Text</p>
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" value="1" name="description_blocks[{{ $lang }}][__block__][items][__item__][headline_line]">
                                        <label class="form-check-label">Linie vor Headline</label>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <x-admin.field.text :name="'description_blocks['. $lang .'][__block__][items][__item__][headline]'" :required="false" :placeholder="'Headline (optional)'" />
                                </div>
                                <div class="col-6 col-lg-4">
                                    <select class="form-select form-select-sm" name="description_blocks[{{ $lang }}][__block__][items][__item__][headline_color]">
                                        <option value="primary" selected>Primary (Dark)</option>
                                        <option value="emerald-950">Emerald 950</option>
                                        <option value="emerald-900">Emerald 900</option>
                                        <option value="emerald-800">Emerald 800</option>
                                        <option value="gold-bright">Gold Bright</option>
                                    </select>
                                </div>
                                <div class="col-6 col-lg-2 d-flex align-items-center">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" value="nicevar" name="description_blocks[{{ $lang }}][__block__][items][__item__][headline_font]">
                                        <label class="form-check-label small">NiceVar</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" value="1" name="description_blocks[{{ $lang }}][__block__][items][__item__][content_line]">
                                        <label class="form-check-label">Linie vor Text</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <x-admin.field.wysiwyg :name="'description_blocks['. $lang .'][__block__][items][__item__][content]'" :placeholder="'Text (optional)'" :height="200" :buttons="'bold|italic|link'" />
                                </div>
                                <div class="col-12 col-lg-6">
                                    <x-admin.field.text :name="'description_blocks['. $lang .'][__block__][items][__item__][link_text]'" :required="false" :placeholder="'Link Text (optional)'" />
                                </div>
                                <div class="col-12 col-lg-6">
                                    <x-admin.field.text :name="'description_blocks['. $lang .'][__block__][items][__item__][link_url]'" :required="false" :placeholder="'Link URL (optional)'" />
                                </div>
                            </div>
                        </div>

                    </div>
                </template>

            </x-admin.tabs.pane>
        @endforeach
    </x-slot:content>
</x-admin.tabs.wrapper>
