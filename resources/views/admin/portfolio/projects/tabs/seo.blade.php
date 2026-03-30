@php $lang = config('app.fallback_locale', 'en'); @endphp

<div class="d-flex flex-column gap-4">
    <!-- seo title -->
    <x-admin.field.text
        :name="'seo_title['. $lang .']'"
        :value="old('seo_title.' . $lang, isset($project) ? $project->getTranslation('seo_title', $lang, false) : null)"
        :placeholder="__('admin.seo_title')"
        :required="false"
    />

    <!-- seo description -->
    <x-admin.field.text
        :name="'seo_description['. $lang .']'"
        :value="old('seo_description.' . $lang, isset($project) ? $project->getTranslation('seo_description', $lang, false) : null)"
        :placeholder="__('admin.seo_description')"
        :required="false"
    />

    <!-- seo keywords -->
    <x-admin.field.text
        :name="'seo_keywords['. $lang .']'"
        :value="old('seo_keywords.' . $lang, isset($project) ? $project->getTranslation('seo_keywords', $lang, false) : null)"
        :placeholder="__('admin.seo_keywords')"
        :required="false"
    />

    <!-- geo text -->
    <x-admin.field.textarea
        :name="'geo_text['. $lang .']'"
        :value="old('geo_text.' . $lang, isset($project) ? $project->getTranslation('geo_text', $lang, false) : null)"
        :placeholder="__('admin.geo_text')"
        :required="false"
    />
</div>
