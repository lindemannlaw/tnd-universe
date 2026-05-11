@php $lang = config('app.fallback_locale', 'en'); @endphp

<div class="d-flex flex-column gap-4">
    <x-admin.field.text
        :name="'public_slug'"
        :value="old('public_slug', $page->public_slug)"
        :placeholder="'URL Slug (leer = /, z. B. start) '"
        :required="false"
    />

    <!-- title -->
    <x-admin.field.text
        :name="'title['. $lang .']'"
        :value="old('title.' . $lang, $page->getTranslation('title', $lang, false))"
        :placeholder="__('admin.title')"
    />

    {{-- SEO / GEO fields are managed centrally on the "SEO / GEO" admin screen (single source of truth). --}}
</div>
