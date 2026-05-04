@php $lang = config('app.fallback_locale', 'en'); @endphp

<div class="grid gap-4">
    <div class="g-col-12 g-col-md-4">
        <!-- image -->
        <x-admin.field.image
            :name="'hero_image'"
            :placeholder="__('admin.image') . ' ( 16 / 9 )'"
            :ratio="'16x9'"
            :fit="'contain'"
            :src="$page->hasMedia('hero-image') ? $page->getFirstMediaUrl('hero-image', 'md-webp') : null"
            :required="!$page->hasMedia('hero-image')"
        />
    </div>

    <div class="g-col-12 g-col-md-8">
        <div class="d-flex flex-column gap-4">
            <x-admin.field.text
                :name="'public_slug'"
                :value="old('public_slug', $page->public_slug)"
                :placeholder="'URL Slug (z. B. contacts)'"
                :required="false"
            />

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

            <!-- geo text -->
            <x-admin.field.textarea
                :name="'geo_text['. $lang .']'"
                :value="old('geo_text.' . $lang, $page->getTranslation('geo_text', $lang, false))"
                :placeholder="__('admin.geo_text')"
                :required="false"
            />
        </div>
    </div>
</div>
