@php
    $lang = config('app.fallback_locale', 'en');
    $detailsList = isset($service) ? data_get($service->getTranslation('details', $lang, false), 'list') : [];
@endphp

<div class="d-flex flex-column gap-4">
    <!-- section title -->
    <x-admin.field.text
        :name="'details['. $lang .'][title]'"
        :value="old('details.' . $lang . '.title', isset($service) ? data_get($service->getTranslation('details', $lang, false), 'title') : null)"
        :placeholder="__('admin.section_title')"
    />

    <hr class="border-4">

    <x-admin.dynamic-fields.wrapper>
        @foreach($detailsList as $detail)
            @php $loopIndex = $loop->index; @endphp

            <x-admin.dynamic-fields.group>
                <div class="d-flex flex-column gap-4">
                    <!-- title -->
                    <x-admin.field.text
                        :name="'details['. $lang .'][list][' . $loopIndex . '][title]'"
                        :value="!empty($detailsList) ? data_get($detailsList, $loopIndex . '.title') : null"
                        :placeholder="__('admin.title')"
                    />

                    <!-- description -->
                    <x-admin.field.wysiwyg
                        :name="'details['. $lang .'][list][' . $loopIndex . '][description]'"
                        :value="!empty($detailsList) ? data_get($detailsList, $loopIndex . '.description') : null"
                        :placeholder="__('admin.description')"
                        :height="100"
                    />
                </div>
            </x-admin.dynamic-fields.group>
        @endforeach

        <x-slot:template>
            <x-admin.dynamic-fields.group>
                <div class="d-flex flex-column gap-4">
                    <!-- title -->
                    <x-admin.field.text
                        :name="'details['. $lang .'][list][0][title]'"
                        :placeholder="__('admin.title')"
                    />

                    <!-- description -->
                    <x-admin.field.wysiwyg
                        :name="'details['. $lang .'][list][0][description]'"
                        :placeholder="__('admin.description')"
                        :height="100"
                    />
                </div>
            </x-admin.dynamic-fields.group>
        </x-slot:template>
    </x-admin.dynamic-fields.wrapper>
</div>
