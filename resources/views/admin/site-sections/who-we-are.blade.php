@extends('admin.layouts.base')

@section('title', __('admin.edit') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel
        :title="__('admin.edit')"
    >
        <a href="{{ route('admin.translations.index', ['type' => 'site_section', 'id' => $section->id]) }}"
           class="btn btn-sm btn-dark d-inline-flex align-items-center gap-1 me-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.913 17H20.087M12.913 17L11 21M12.913 17L15.7783 11.009C16.0092 10.5263 16.1246 10.2849 16.2826 10.2086C16.4199 10.1423 16.5801 10.1423 16.7174 10.2086C16.8754 10.2849 16.9908 10.5263 17.2217 11.009L20.087 17M20.087 17L22 21M2 5H8M8 5H11.5M8 5V3M11.5 5H14M11.5 5C11.0039 7.95729 9.85259 10.6362 8.16555 12.8844M10 14C9.38747 13.7248 8.76265 13.3421 8.16555 12.8844M8.16555 12.8844C6.81302 11.8478 5.60276 10.4266 5 9M8.16555 12.8844C6.56086 15.0229 4.47143 16.7718 2 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Übersetzungen
        </a>
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
                        :src="$section->hasAttachedMedia('back-image') ? $section->firstAttachedMediaUrl('back-image', 'md-webp') : null"
                        :required="!$section->hasAttachedMedia('back-image')"
                    />
                </div>

                <div class="g-col-12 g-col-xs-6 g-col-md-12 g-col-xxl-6">
                    <!-- front image -->
                    <x-admin.field.image
                        :name="'front_image'"
                        :placeholder="__('admin.front_image') . ' ( 5 / 6 )'"
                        :ratio="'5x6'"
                        :src="$section->hasAttachedMedia('front-image') ? $section->firstAttachedMediaUrl('front-image', 'md-webp') : null"
                        :required="!$section->hasAttachedMedia('front-image')"
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
