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
        :action="route('admin.portfolio.page.update', $page->id)"
        :method="'PATCH'"
    >
        <div class="d-flex flex-column gap-4">
            <!-- title -->
            <x-admin.field.text
                :name="'title['. $lang .']'"
                :value="old('title.' . $lang, $page->getTranslation('title', $lang, false))"
                :placeholder="__('admin.title')"
            />

            <!-- seo title -->
            <x-admin.field.text
                :name="'seo_title['. $lang .']'"
                :value="old('seo_title.' . $lang, $page->getTranslation('seo_title', $lang, false))"
                :placeholder="__('admin.seo_title')"
                :required="false"
            />

            <!-- seo description -->
            <x-admin.field.text
                :name="'seo_description['. $lang .']'"
                :value="old('seo_description.' . $lang, $page->getTranslation('seo_description', $lang, false))"
                :placeholder="__('admin.seo_description')"
                :required="false"
            />

            <!-- seo keywords -->
            <x-admin.field.text
                :name="'seo_keywords['. $lang .']'"
                :value="old('seo_keywords.' . $lang, $page->getTranslation('seo_keywords', $lang, false))"
                :placeholder="__('admin.seo_keywords')"
                :required="false"
            />
        </div>
    </x-admin.container>
@endsection
