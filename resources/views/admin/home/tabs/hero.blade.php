@php $lang = config('app.fallback_locale', 'en'); @endphp

<div class="grid gap-4">
    <div class="g-col-12 g-col-md-4">
        <!-- image -->
        <x-admin.field.image
            :name="'hero_image'"
            :placeholder="__('admin.bg_image') . ' ( 3 / 2 )'"
            :ratio="'3x2'"
            :src="$page->hasMedia('hero-image') ? $page->getFirstMediaUrl('hero-image', 'md-webp') : null"
            :required="!$page->hasMedia('hero-image')"
        />
    </div>

    <div class="g-col-12 g-col-md-8">
        <div class="d-flex flex-column gap-4">
            <!-- title -->
            <x-admin.field.text
                :name="'content_data['. $lang .'][hero][title]'"
                :value="old('content_data.' . $lang . '.hero.title', data_get($page->getTranslation('content_data', $lang, false), 'hero.title'))"
                :placeholder="__('admin.title')"
            />

            <!-- description -->
            <x-admin.field.wysiwyg
                :name="'content_data['. $lang .'][hero][description]'"
                :value="old('content_data.' . $lang . '.hero.description', data_get($page->getTranslation('content_data', $lang, false), 'hero.description'))"
                :placeholder="__('admin.description')"
                :height="100"
                :required="false"
            />
        </div>
    </div>
</div>
