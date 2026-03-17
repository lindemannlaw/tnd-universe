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
                @endphp

                <div
                    class="d-flex flex-column gap-4"
                    data-project-description-builder
                    data-locale="{{ $lang }}"
                >
                    <div
                        class="d-flex flex-column gap-4"
                        data-blocks-wrapper
                    >
                        @foreach($blocks as $blockIndex => $block)
                            <div class="border rounded p-3 d-flex flex-column gap-3 bg-white" data-block>
                                <input type="hidden" name="description_blocks[{{ $lang }}][{{ $blockIndex }}][type]" value="{{ data_get($block, 'type', 'text') }}" data-block-type-input>

                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold me-1" data-block-label>Block</span>

                                    <select class="form-select w-auto" data-block-type-select>
                                        <option value="text" {{ data_get($block, 'type', 'text') === 'text' ? 'selected' : null }}>Text</option>
                                        <option value="floating_gallery" {{ data_get($block, 'type') === 'floating_gallery' ? 'selected' : null }}>Floating Image Gallery</option>
                                        <option value="text_column" {{ data_get($block, 'type') === 'text_column' ? 'selected' : null }}>Text Column</option>
                                    </select>

                                    <x-admin.button
                                        data-block-add-after
                                        class="p-2 ms-auto"
                                        :btn="'btn-outline-success'"
                                        :iconName="'plus-circle'"
                                    />
                                    <x-admin.button
                                        data-block-remove
                                        class="p-2"
                                        :btn="'btn-outline-danger'"
                                        :iconName="'dash-circle'"
                                    />
                                    <x-admin.button
                                        data-block-move
                                        class="p-2"
                                        :btn="'btn-outline-secondary'"
                                        :iconName="'arrows-move'"
                                    />
                                    <button type="button" class="btn btn-outline-secondary p-2" data-block-toggle aria-expanded="false">
                                        <span data-block-toggle-icon>></span>
                                    </button>
                                </div>

                                <div class="d-flex flex-column gap-3 d-none" data-block-body>
                                    <div data-block-type-panel="text" class="{{ data_get($block, 'type', 'text') === 'text' ? null : 'd-none' }}">
                                        <x-admin.field.wysiwyg
                                            :name="'description_blocks['. $lang .'][' . $blockIndex . '][content]'"
                                            :placeholder="__('admin.description')"
                                            :value="data_get($block, 'content')"
                                            :height="300"
                                            :buttons="'blockquote|list|image|video'"
                                        />
                                    </div>

                                    <div data-block-type-panel="text_column" class="d-flex flex-column gap-3 {{ data_get($block, 'type') === 'text_column' ? null : 'd-none' }}">

                                        {{-- 1. CONTAINER --}}
                                        <p class="text-muted small mb-0 fw-semibold">Container</p>
                                        <div class="row g-3">
                                            <div class="col-6 col-lg-3">
                                                <x-admin.field.number
                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][col_span]'"
                                                    :value="data_get($block, 'col_span', 12)"
                                                    :placeholder="'Anzahl Spalten (1-12)'"
                                                    :fieldAttrs="'min=1 max=12'"
                                                />
                                            </div>
                                            <div class="col-6 col-lg-3">
                                                <x-admin.field.number
                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][col_start]'"
                                                    :value="data_get($block, 'col_start', 1)"
                                                    :placeholder="'Start Spalte (1-12)'"
                                                    :fieldAttrs="'min=1 max=12'"
                                                />
                                            </div>
                                            <div class="col-6 col-lg-3">
                                                <x-admin.field.number
                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][padding_top]'"
                                                    :value="data_get($block, 'padding_top', 0)"
                                                    :placeholder="'Padding oben (px)'"
                                                    :fieldAttrs="'min=0 max=300 step=4'"
                                                />
                                            </div>
                                            <div class="col-6 col-lg-3">
                                                <x-admin.field.number
                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][padding_bottom]'"
                                                    :value="data_get($block, 'padding_bottom', 0)"
                                                    :placeholder="'Padding unten (px)'"
                                                    :fieldAttrs="'min=0 max=300 step=4'"
                                                />
                                            </div>
                                        </div>

                                        {{-- 2. BILD --}}
                                        <div class="border-top pt-3">
                                            <p class="text-muted small mb-2 fw-semibold">Bild (optional)</p>
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <x-admin.field.image
                                                        :name="'description_blocks['. $lang .'][' . $blockIndex . '][image_file]'"
                                                        :src="data_get($block, 'image')"
                                                        :required="false"
                                                        :ratio="'16x9'"
                                                        :placeholder="'Bild hochladen'"
                                                    />
                                                </div>
                                                <div class="col-6 col-lg-3">
                                                    <label class="form-label small">Alignment</label>
                                                    <select class="form-select" name="description_blocks[{{ $lang }}][{{ $blockIndex }}][image_alignment]">
                                                        <option value="top" {{ data_get($block, 'image_alignment', 'top') === 'top' ? 'selected' : '' }}>Oben</option>
                                                        <option value="left" {{ data_get($block, 'image_alignment') === 'left' ? 'selected' : '' }}>Links</option>
                                                        <option value="right" {{ data_get($block, 'image_alignment') === 'right' ? 'selected' : '' }}>Rechts</option>
                                                    </select>
                                                </div>
                                                <div class="col-6 col-lg-3">
                                                    <x-admin.field.number
                                                        :name="'description_blocks['. $lang .'][' . $blockIndex . '][image_col_span]'"
                                                        :value="data_get($block, 'image_col_span', 12)"
                                                        :placeholder="'Bild Spalten (1-12)'"
                                                        :fieldAttrs="'min=1 max=12'"
                                                    />
                                                </div>
                                                <div class="col-6 col-lg-3">
                                                    <x-admin.field.number
                                                        :name="'description_blocks['. $lang .'][' . $blockIndex . '][text_col_span]'"
                                                        :value="data_get($block, 'text_col_span', 12)"
                                                        :placeholder="'Text Spalten (1-12)'"
                                                        :fieldAttrs="'min=1 max=12'"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 3. TEXT / INHALT --}}
                                        <div class="border-top pt-3">
                                            <p class="text-muted small mb-2 fw-semibold">Text</p>
                                            <div class="row g-3">
                                                <div class="col-12 col-lg-6">
                                                    <x-admin.field.text
                                                        :name="'description_blocks['. $lang .'][' . $blockIndex . '][headline]'"
                                                        :value="data_get($block, 'headline')"
                                                        :required="false"
                                                        :placeholder="'Headline (optional)'"
                                                    />
                                                </div>
                                                <div class="col-6 col-lg-3">
                                                    <select class="form-select" name="description_blocks[{{ $lang }}][{{ $blockIndex }}][headline_color]">
                                                        @foreach(['primary' => 'Primary (Dark)', 'emerald-950' => 'Emerald 950', 'emerald-900' => 'Emerald 900', 'emerald-800' => 'Emerald 800', 'gold-bright' => 'Gold Bright'] as $val => $label)
                                                            <option value="{{ $val }}" {{ data_get($block, 'headline_color', 'primary') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-6 col-lg-3">
                                                    <select class="form-select" name="description_blocks[{{ $lang }}][{{ $blockIndex }}][headline_font]">
                                                        <option value="pangea" {{ data_get($block, 'headline_font', 'pangea') === 'pangea' ? 'selected' : '' }}>Pangea</option>
                                                        <option value="nicevar" {{ data_get($block, 'headline_font') === 'nicevar' ? 'selected' : '' }}>NiceVar Ultra Light</option>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" value="1"
                                                            name="description_blocks[{{ $lang }}][{{ $blockIndex }}][headline_line]"
                                                            id="hl_line_{{ $lang }}_{{ $blockIndex }}"
                                                            {{ data_get($block, 'headline_line') ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="hl_line_{{ $lang }}_{{ $blockIndex }}">Linie vor Headline</label>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <x-admin.field.wysiwyg
                                                        :name="'description_blocks['. $lang .'][' . $blockIndex . '][content]'"
                                                        :placeholder="'Text (optional)'"
                                                        :value="data_get($block, 'content')"
                                                        :height="200"
                                                        :buttons="'bold|italic|link'"
                                                    />
                                                </div>
                                                <div class="col-12">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" value="1"
                                                            name="description_blocks[{{ $lang }}][{{ $blockIndex }}][content_line]"
                                                            id="content_line_{{ $lang }}_{{ $blockIndex }}"
                                                            {{ data_get($block, 'content_line') ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="content_line_{{ $lang }}_{{ $blockIndex }}">Linie vor Text</label>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-lg-6">
                                                    <x-admin.field.text
                                                        :name="'description_blocks['. $lang .'][' . $blockIndex . '][link_text]'"
                                                        :value="data_get($block, 'link_text')"
                                                        :required="false"
                                                        :placeholder="'Link Text (optional)'"
                                                    />
                                                </div>
                                                <div class="col-12 col-lg-6">
                                                    <x-admin.field.text
                                                        :name="'description_blocks['. $lang .'][' . $blockIndex . '][link_url]'"
                                                        :value="data_get($block, 'link_url')"
                                                        :required="false"
                                                        :placeholder="'Link URL (optional)'"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div data-block-type-panel="floating_gallery" class="d-flex flex-column gap-3 {{ data_get($block, 'type') === 'floating_gallery' ? null : 'd-none' }}">
                                        <div
                                            class="d-flex flex-column gap-3"
                                            data-gallery-items-wrapper
                                        >
                                            @foreach((data_get($block, 'items') ?: []) as $itemIndex => $item)
                                                <div class="border rounded p-3 d-flex flex-column gap-3" data-gallery-item>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <x-admin.button
                                                            data-gallery-item-move
                                                            class="p-2"
                                                            :btn="'btn-outline-secondary'"
                                                            :iconName="'arrows-move'"
                                                        />
                                                        <x-admin.button
                                                            data-gallery-item-add-after
                                                            class="p-2 ms-auto"
                                                            :btn="'btn-outline-success'"
                                                            :iconName="'plus-circle'"
                                                        />
                                                        <x-admin.button
                                                            data-gallery-item-remove
                                                            class="p-2"
                                                            :btn="'btn-outline-danger'"
                                                            :iconName="'dash-circle'"
                                                        />
                                                    </div>

                                                    <x-admin.field.image
                                                        :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][image_file]'"
                                                        :placeholder="'Image'"
                                                        :src="data_get($item, 'image')"
                                                        :required="false"
                                                        :ratio="'4x3'"
                                                        :fit="'contain'"
                                                    />
                                                    <input type="hidden" name="description_blocks[{{ $lang }}][{{ $blockIndex }}][items][{{ $itemIndex }}][image]" value="{{ data_get($item, 'image') }}" data-gallery-item-image-hidden>

                                                    <div class="row g-3">
                                                        <div class="col-12 col-lg-6">
                                                            <x-admin.field.text
                                                                :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][headline]'"
                                                                :value="data_get($item, 'headline')"
                                                                :required="false"
                                                                :placeholder="'Headline (optional)'"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-lg-6">
                                                            <x-admin.field.text
                                                                :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][subhead]'"
                                                                :value="data_get($item, 'subhead')"
                                                                :required="false"
                                                                :placeholder="'Subhead (optional)'"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-lg-6">
                                                            <x-admin.field.number
                                                                :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][col_span]'"
                                                                :value="data_get($item, 'col_span', 12)"
                                                                :placeholder="'Columns (1-12)'"
                                                                :fieldAttrs="'min=1 max=12'"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-lg-6">
                                                            <x-admin.field.number
                                                                :name="'description_blocks['. $lang .'][' . $blockIndex . '][items][' . $itemIndex . '][col_start]'"
                                                                :value="data_get($item, 'col_start', 1)"
                                                                :placeholder="'Start column (1-12)'"
                                                                :fieldAttrs="'min=1 max=12'"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <x-admin.button
                                            data-gallery-item-add
                                            class="ms-auto"
                                            :btn="'btn-outline-primary'"
                                            :title="'Add image'"
                                            :iconName="'plus-circle'"
                                        />
                                        <div class="row g-3 mt-1">
                                            <div class="col-6">
                                                <x-admin.field.number
                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][padding_top]'"
                                                    :value="data_get($block, 'padding_top', 0)"
                                                    :placeholder="'Padding oben (px)'"
                                                    :fieldAttrs="'min=0 max=300 step=4'"
                                                />
                                            </div>
                                            <div class="col-6">
                                                <x-admin.field.number
                                                    :name="'description_blocks['. $lang .'][' . $blockIndex . '][padding_bottom]'"
                                                    :value="data_get($block, 'padding_bottom', 0)"
                                                    :placeholder="'Padding unten (px)'"
                                                    :fieldAttrs="'min=0 max=300 step=4'"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <x-admin.button
                        data-block-add
                        class="ms-auto"
                        :btn="'btn-outline-primary'"
                        :title="'Add block'"
                        :iconName="'plus-circle'"
                    />
                </div>

                <template data-block-template="text">
                    <div class="border rounded p-3 d-flex flex-column gap-3 bg-white" data-block>
                        <input type="hidden" name="description_blocks[{{ $lang }}][__block__][type]" value="text" data-block-type-input>

                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-semibold me-1" data-block-label>Block</span>
                            <select class="form-select w-auto" data-block-type-select>
                                <option value="text" selected>Text</option>
                                <option value="floating_gallery">Floating Image Gallery</option>
                                <option value="text_column">Text Column</option>
                            </select>
                            <x-admin.button
                                data-block-add-after
                                class="p-2 ms-auto"
                                :btn="'btn-outline-success'"
                                :iconName="'plus-circle'"
                            />
                            <x-admin.button
                                data-block-remove
                                class="p-2"
                                :btn="'btn-outline-danger'"
                                :iconName="'dash-circle'"
                            />
                            <x-admin.button
                                data-block-move
                                class="p-2"
                                :btn="'btn-outline-secondary'"
                                :iconName="'arrows-move'"
                            />
                            <button type="button" class="btn btn-outline-secondary p-2" data-block-toggle aria-expanded="false">
                                <span data-block-toggle-icon>></span>
                            </button>
                        </div>

                        <div class="d-flex flex-column gap-3 d-none" data-block-body>
                            <div data-block-type-panel="text">
                                <x-admin.field.wysiwyg
                                    :name="'description_blocks['. $lang .'][__block__][content]'"
                                    :placeholder="__('admin.description')"
                                    :height="300"
                                    :buttons="'blockquote|list|image|video'"
                                />
                            </div>

                            <div data-block-type-panel="floating_gallery" class="d-flex flex-column gap-3 d-none">
                                <div class="d-flex flex-column gap-3" data-gallery-items-wrapper></div>
                                <x-admin.button
                                    data-gallery-item-add
                                    class="ms-auto"
                                    :btn="'btn-outline-primary'"
                                    :title="'Add image'"
                                    :iconName="'plus-circle'"
                                />
                                <div class="row g-3 mt-1">
                                    <div class="col-6">
                                        <x-admin.field.number
                                            :name="'description_blocks['. $lang .'][__block__][padding_top]'"
                                            :value="0"
                                            :placeholder="'Padding oben (px)'"
                                            :fieldAttrs="'min=0 max=300 step=4'"
                                        />
                                    </div>
                                    <div class="col-6">
                                        <x-admin.field.number
                                            :name="'description_blocks['. $lang .'][__block__][padding_bottom]'"
                                            :value="0"
                                            :placeholder="'Padding unten (px)'"
                                            :fieldAttrs="'min=0 max=300 step=4'"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div data-block-type-panel="text_column" class="d-flex flex-column gap-3 d-none">

                                {{-- 1. CONTAINER --}}
                                <p class="text-muted small mb-0 fw-semibold">Container</p>
                                <div class="row g-3">
                                    <div class="col-6 col-lg-3">
                                        <x-admin.field.number
                                            :name="'description_blocks['. $lang .'][__block__][col_span]'"
                                            :value="12"
                                            :placeholder="'Anzahl Spalten (1-12)'"
                                            :fieldAttrs="'min=1 max=12'"
                                        />
                                    </div>
                                    <div class="col-6 col-lg-3">
                                        <x-admin.field.number
                                            :name="'description_blocks['. $lang .'][__block__][col_start]'"
                                            :value="1"
                                            :placeholder="'Start Spalte (1-12)'"
                                            :fieldAttrs="'min=1 max=12'"
                                        />
                                    </div>
                                    <div class="col-6 col-lg-3">
                                        <x-admin.field.number
                                            :name="'description_blocks['. $lang .'][__block__][padding_top]'"
                                            :value="0"
                                            :placeholder="'Padding oben (px)'"
                                            :fieldAttrs="'min=0 max=300 step=4'"
                                        />
                                    </div>
                                    <div class="col-6 col-lg-3">
                                        <x-admin.field.number
                                            :name="'description_blocks['. $lang .'][__block__][padding_bottom]'"
                                            :value="0"
                                            :placeholder="'Padding unten (px)'"
                                            :fieldAttrs="'min=0 max=300 step=4'"
                                        />
                                    </div>
                                </div>

                                {{-- 2. BILD --}}
                                <div class="border-top pt-3">
                                    <p class="text-muted small mb-2 fw-semibold">Bild (optional)</p>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <x-admin.field.image
                                                :name="'description_blocks['. $lang .'][__block__][image_file]'"
                                                :required="false"
                                                :ratio="'16x9'"
                                                :placeholder="'Bild hochladen'"
                                            />
                                        </div>
                                        <div class="col-6 col-lg-3">
                                            <label class="form-label small">Alignment</label>
                                            <select class="form-select" name="description_blocks[{{ $lang }}][__block__][image_alignment]">
                                                <option value="top" selected>Oben</option>
                                                <option value="left">Links</option>
                                                <option value="right">Rechts</option>
                                            </select>
                                        </div>
                                        <div class="col-6 col-lg-3">
                                            <x-admin.field.number
                                                :name="'description_blocks['. $lang .'][__block__][image_col_span]'"
                                                :value="12"
                                                :placeholder="'Bild Spalten (1-12)'"
                                                :fieldAttrs="'min=1 max=12'"
                                            />
                                        </div>
                                        <div class="col-6 col-lg-3">
                                            <x-admin.field.number
                                                :name="'description_blocks['. $lang .'][__block__][text_col_span]'"
                                                :value="12"
                                                :placeholder="'Text Spalten (1-12)'"
                                                :fieldAttrs="'min=1 max=12'"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {{-- 3. TEXT / INHALT --}}
                                <div class="border-top pt-3">
                                    <p class="text-muted small mb-2 fw-semibold">Text</p>
                                    <div class="row g-3">
                                        <div class="col-12 col-lg-6">
                                            <x-admin.field.text
                                                :name="'description_blocks['. $lang .'][__block__][headline]'"
                                                :required="false"
                                                :placeholder="'Headline (optional)'"
                                            />
                                        </div>
                                        <div class="col-6 col-lg-3">
                                            <select class="form-select" name="description_blocks[{{ $lang }}][__block__][headline_color]">
                                                <option value="primary" selected>Primary (Dark)</option>
                                                <option value="emerald-950">Emerald 950</option>
                                                <option value="emerald-900">Emerald 900</option>
                                                <option value="emerald-800">Emerald 800</option>
                                                <option value="gold-bright">Gold Bright</option>
                                            </select>
                                        </div>
                                        <div class="col-6 col-lg-3">
                                            <select class="form-select" name="description_blocks[{{ $lang }}][__block__][headline_font]">
                                                <option value="pangea" selected>Pangea</option>
                                                <option value="nicevar">NiceVar Ultra Light</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" value="1"
                                                    name="description_blocks[{{ $lang }}][__block__][headline_line]">
                                                <label class="form-check-label">Linie vor Headline</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <x-admin.field.wysiwyg
                                                :name="'description_blocks['. $lang .'][__block__][content]'"
                                                :placeholder="'Text (optional)'"
                                                :height="200"
                                                :buttons="'bold|italic|link'"
                                            />
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" value="1"
                                                    name="description_blocks[{{ $lang }}][__block__][content_line]">
                                                <label class="form-check-label">Linie vor Text</label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <x-admin.field.text
                                                :name="'description_blocks['. $lang .'][__block__][link_text]'"
                                                :required="false"
                                                :placeholder="'Link Text (optional)'"
                                            />
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <x-admin.field.text
                                                :name="'description_blocks['. $lang .'][__block__][link_url]'"
                                                :required="false"
                                                :placeholder="'Link URL (optional)'"
                                            />
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </template>

                <template data-gallery-item-template>
                    <div class="border rounded p-3 d-flex flex-column gap-3" data-gallery-item>
                        <div class="d-flex align-items-center gap-2">
                            <x-admin.button
                                data-gallery-item-move
                                class="p-2"
                                :btn="'btn-outline-secondary'"
                                :iconName="'arrows-move'"
                            />
                            <x-admin.button
                                data-gallery-item-add-after
                                class="p-2 ms-auto"
                                :btn="'btn-outline-success'"
                                :iconName="'plus-circle'"
                            />
                            <x-admin.button
                                data-gallery-item-remove
                                class="p-2"
                                :btn="'btn-outline-danger'"
                                :iconName="'dash-circle'"
                            />
                        </div>

                        <x-admin.field.image
                            :name="'description_blocks['. $lang .'][__block__][items][__item__][image_file]'"
                            :placeholder="'Image'"
                            :required="false"
                            :ratio="'4x3'"
                            :fit="'contain'"
                        />
                        <input type="hidden" name="description_blocks[{{ $lang }}][__block__][items][__item__][image]" value="" data-gallery-item-image-hidden>

                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                <x-admin.field.text
                                    :name="'description_blocks['. $lang .'][__block__][items][__item__][headline]'"
                                    :required="false"
                                    :placeholder="'Headline (optional)'"
                                />
                            </div>
                            <div class="col-12 col-lg-6">
                                <x-admin.field.text
                                    :name="'description_blocks['. $lang .'][__block__][items][__item__][subhead]'"
                                    :required="false"
                                    :placeholder="'Subhead (optional)'"
                                />
                            </div>
                            <div class="col-12 col-lg-6">
                                <x-admin.field.number
                                    :name="'description_blocks['. $lang .'][__block__][items][__item__][col_span]'"
                                    :value="12"
                                    :placeholder="'Columns (1-12)'"
                                    :fieldAttrs="'min=1 max=12'"
                                />
                            </div>
                            <div class="col-12 col-lg-6">
                                <x-admin.field.number
                                    :name="'description_blocks['. $lang .'][__block__][items][__item__][col_start]'"
                                    :value="1"
                                    :placeholder="'Start column (1-12)'"
                                    :fieldAttrs="'min=1 max=12'"
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </x-admin.tabs.pane>
        @endforeach
    </x-slot:content>
</x-admin.tabs.wrapper>
