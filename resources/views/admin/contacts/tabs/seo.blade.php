@php $lang = config('app.fallback_locale', 'en'); @endphp

<div class="grid gap-4">
    <div class="g-col-12 g-col-md-4">
        <!-- image -->
        <x-admin.field.image
            :name="'hero_image'"
            :placeholder="__('admin.image') . ' ( 16 / 9 )'"
            :ratio="'16x9'"
            :fit="'contain'"
            :src="$page->hasAttachedMedia('hero-image') ? $page->firstAttachedMediaUrl('hero-image', 'md-webp') : null"
            :required="!$page->hasAttachedMedia('hero-image')"
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

            {{-- SEO / GEO fields are managed centrally on the "SEO / GEO" admin screen (single source of truth). --}}
        </div>
    </div>
</div>
