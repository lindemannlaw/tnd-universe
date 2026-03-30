@extends('admin.layouts.base')

@section('title', __('admin.home') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel
        :title="__('admin.home')"
    >
        <a href="{{ route('admin.seo-geo.show', ['type' => 'page', 'id' => $page->id]) }}"
           class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 me-2">
            <svg class="bi" width="13" height="13" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#stars"/></svg>
            SEO / GEO
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
    <x-admin.container
        :id="'controlForm'"
        :action="route('admin.imprint.page.update', $page->id)"
        :method="'PATCH'"
    >
        <x-admin.tabs.wrapper>
            <x-slot:nav>
                @foreach(supported_languages_keys() as $lang)
                    <x-admin.tabs.nav-item
                        :is-active="$loop->first"
                        :target="'seo-locale-' . $lang"
                        :title="$lang"
                    />
                @endforeach
            </x-slot:nav>

            <x-slot:content>
                @foreach(supported_languages_keys() as $lang)
                    <x-admin.tabs.pane :is-active="$loop->first" :id="'seo-locale-' . $lang">
                        <div class="d-flex flex-column gap-4">
                            <!-- title -->
                            <x-admin.field.text
                                :name="'title['. $lang .']'"
                                :value="old('title.' . $lang, $page->getTranslation('title', $lang))"
                                :placeholder="__('admin.title')"
                            />

                            <!-- seo title -->
                            <x-admin.field.text
                                :name="'seo_title['. $lang .']'"
                                :value="old('seo_title.' . $lang, $page->getTranslation('seo_title', $lang))"
                                :placeholder="__('admin.seo_title')"
                                :required="false"
                            />

                            <!-- seo description -->
                            <x-admin.field.text
                                :name="'seo_description['. $lang .']'"
                                :value="old('seo_description.' . $lang, $page->getTranslation('seo_description', $lang))"
                                :placeholder="__('admin.seo_description')"
                                :required="false"
                            />

                            <!-- seo keywords -->
                            <x-admin.field.text
                                :name="'seo_keywords['. $lang .']'"
                                :value="old('seo_keywords.' . $lang, $page->getTranslation('seo_keywords', $lang))"
                                :placeholder="__('admin.seo_keywords')"
                                :required="false"
                            />

                            <!-- description -->
                            <x-admin.field.wysiwyg
                                :name="'description['. $lang .']'"
                                :value="old('description.' . $lang, $page->getTranslation('description', $lang))"
                                :placeholder="__('admin.description')"
                                :buttons="'list|fontColor'"
                            />
                        </div>
                    </x-admin.tabs.pane>
                @endforeach
            </x-slot:content>
        </x-admin.tabs.wrapper>

    </x-admin.container>
@endsection
