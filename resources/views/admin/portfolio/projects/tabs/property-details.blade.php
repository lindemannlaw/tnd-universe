@php $lang = config('app.fallback_locale', 'en'); @endphp
<div class="d-flex flex-column gap-4">
    <!-- type -->
    <x-admin.field.text
        :name="'property_details[' . $lang . '][property_type]'"
        :placeholder="__('base.property_type')"
        :value="isset($project) ? data_get($project->getTranslations('property_details'), $lang . '.property_type') : null"
        :required="false"
    />

    <!-- status -->
    <x-admin.field.text
        :name="'property_details[' . $lang . '][status]'"
        :placeholder="__('base.status')"
        :value="isset($project) ? data_get($project->getTranslations('property_details'), $lang . '.status') : null"
        :required="false"
    />

    <!-- year built -->
    <x-admin.field.text
        :name="'property_details[' . $lang . '][year_built]'"
        :placeholder="__('base.year_built')"
        :value="isset($project) ? data_get($project->getTranslations('property_details'), $lang . '.year_built') : null"
        :required="false"
    />

    <hr class="my-1">

    <!-- inquiry button text -->
    <x-admin.field.text
        :name="'property_details[' . $lang . '][inquiry_button_text]'"
        :placeholder="__('admin.inquiry_button_text')"
        :value="isset($project)
            ? data_get($project->getTranslations('property_details'), $lang . '.inquiry_button_text')
            : 'Inquire for Details...'"
        :required="false"
    />

    <!-- Toggle (applies globally) -->
    <div class="mt-3 pt-3 border-top">
        <x-admin.field.radio-switch
            class="m-0 me-auto"
            :name="'inquiry_button_active'"
            :title="__('admin.inquiry_button_active')"
            :checked="isset($project) ? $project->inquiry_button_active : false"
        />
    </div>
</div>
