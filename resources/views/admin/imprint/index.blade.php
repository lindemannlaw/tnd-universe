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
        <a href="{{ route('admin.translations.index', ['type' => 'page', 'id' => $page->id]) }}"
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
        :action="route('admin.imprint.page.update', $page->id)"
        :method="'PATCH'"
    >
        <div class="d-flex flex-column gap-4">
            <x-admin.field.text
                :name="'public_slug'"
                :value="old('public_slug', $page->public_slug)"
                :placeholder="'URL Slug (z. B. imprint)'"
                :required="false"
            />

            <!-- title -->
            <x-admin.field.text
                :name="'title['. $lang .']'"
                :value="old('title.' . $lang, $page->getTranslation('title', $lang, false))"
                :placeholder="__('admin.title')"
            />

            {{-- SEO / GEO fields are managed centrally on the "SEO / GEO" admin screen (single source of truth). --}}

            <!-- description -->
            <x-admin.field.wysiwyg
                :name="'description['. $lang .']'"
                :value="old('description.' . $lang, $page->getTranslation('description', $lang, false))"
                :placeholder="__('admin.description')"
                :buttons="'list|fontColor'"
            />
        </div>
    </x-admin.container>
@endsection
