@php $lang = config('app.fallback_locale', 'en'); @endphp

<div class="grid gap-4">
    <div class="g-col-12 g-col-md-4">
        <!-- image -->
        <x-admin.field.image
            :name="'hero_image'"
            :placeholder="__('admin.bg_image') . ' ( 16 / 9 )'"
            :ratio="'16x9'"
            :fit="'contain'"
            :src="$page->hasAttachedMedia('hero-image') ? $page->firstAttachedMediaUrl('hero-image', 'md-webp') : null"
            :required="!$page->hasAttachedMedia('hero-image')"
        />
    </div>

    <div class="g-col-12 g-col-md-8">
        <div class="d-flex flex-column gap-4">
            <!-- title -->
            <x-admin.field.text
                :name="'title['. $lang .']'"
                :value="old('title.' . $lang, $page->getTranslation('title', $lang, false))"
                :placeholder="__('admin.title')"
            />

            <!-- subtitle -->
            <x-admin.field.text
                :name="'content_data['. $lang .'][subtitle]'"
                :value="old('content_data.' . $lang . '.subtitle', data_get($page->getTranslation('content_data', $lang, false), 'subtitle'))"
                :placeholder="__('admin.subtitle')"
            />

            <!-- description -->
            <x-admin.field.textarea
                :name="'content_data['. $lang .'][description]'"
                :value="old('content_data.' . $lang . '.description', data_get($page->getTranslation('content_data', $lang, false), 'description'))"
                :placeholder="__('admin.description')"
            />
        </div>
    </div>
</div>
