@extends('admin.layouts.base')

@section('title', __('admin.edit') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel
        :title="__('admin.edit')"
    >
        <x-admin.button
            data-submit-loader
            :type="'submit'"
            :form="'controlForm'"
            :title="__('admin.save')"
            :iconName="'floppy'"
            :withLoader="true"
            :withMiniStyle="true"
        />
    </x-admin.main-panel>
@endsection

@section('content')
    @php $lang = config('app.fallback_locale', 'en'); @endphp

    <x-admin.container
        :id="'controlForm'"
        :action="route('admin.site-sections.who-we-are.update', $section->id)"
        :method="'PATCH'"
    >
        <div class="grid gap-4">
            <div class="grid gap-4 g-col-12 g-col-md-5">
                <div class="g-col-12 g-col-xs-6 g-col-md-12 g-col-xxl-6">
                    <!-- back image -->
                    <x-admin.field.image
                        :name="'back_image'"
                        :placeholder="__('admin.back_image') . ' ( 5 / 6 )'"
                        :ratio="'5x6'"
                        :src="$section->hasMedia('back-image') ? $section->getFirstMediaUrl('back-image', 'md-webp') : null"
                        :required="!$section->hasMedia('back-image')"
                    />
                </div>

                <div class="g-col-12 g-col-xs-6 g-col-md-12 g-col-xxl-6">
                    <!-- front image -->
                    <x-admin.field.image
                        :name="'front_image'"
                        :placeholder="__('admin.front_image') . ' ( 5 / 6 )'"
                        :ratio="'5x6'"
                        :src="$section->hasMedia('front-image') ? $section->getFirstMediaUrl('front-image', 'md-webp') : null"
                        :required="!$section->hasMedia('front-image')"
                    />
                </div>
            </div>

            <div class="d-flex flex-column gap-4 g-col-12 g-col-md-7">
                <!-- title -->
                <x-admin.field.text
                    :name="'title['. $lang .']'"
                    :value="old('title.' . $lang, $section->getTranslation('title', $lang, false) ?? '')"
                    :placeholder="__('admin.title')"
                />

                <!-- vision -->
                <x-admin.field.wysiwyg
                    :name="'content_data['. $lang .'][vision]'"
                    :value="old('content_data.' . $lang . '.vision', data_get($section->getTranslation('content_data', $lang, false), 'vision'))"
                    :placeholder="__('admin.vision')"
                    :height="100"
                />

                <!-- mission -->
                <x-admin.field.wysiwyg
                    :name="'content_data['. $lang .'][mission]'"
                    :value="old('content_data.' . $lang . '.mission', data_get($section->getTranslation('content_data', $lang, false), 'mission'))"
                    :placeholder="__('admin.mission')"
                    :height="100"
                />
            </div>
        </div>
    </x-admin.container>
@endsection
