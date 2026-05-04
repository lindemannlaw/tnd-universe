@php $lang = config('app.fallback_locale', 'en'); @endphp

<div class="grid gap-4">
    <div class="d-flex flex-column gap-4 g-col-12 g-col-lg-4 g-col-xl-3">
        <!-- hero image -->
        <x-admin.field.image
            :name="'info_image'"
            :placeholder="__('admin.info_image') . ' ( 5 / 6 )'"
            :ratio="'5x6'"
            :src="isset($service) && $service->hasAttachedMedia($service->mediaInfo) ? $service->firstAttachedMediaUrl($service->mediaInfo, 'md-webp') : null"
            :required="isset($service) ? !$service->hasAttachedMedia($service->mediaInfo) : true"
        />
    </div>

    <div class="d-flex flex-column gap-4 g-col-12 g-col-lg-8 g-col-xl-9">
        <!-- section title -->
        <x-admin.field.text
            :name="'info['. $lang .'][title]'"
            :value="old('info.' . $lang . '.title', isset($service) ? data_get($service->getTranslation('info', $lang, false), 'title') : null)"
            :placeholder="__('admin.section_title')"
        />
    </div>
</div>
