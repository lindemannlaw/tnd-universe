@php $lang = config('app.fallback_locale', 'en'); @endphp

<div class="d-flex flex-column gap-4">
    <!-- name -->
    <x-admin.field.text
        :name="'name['. $lang .']'"
        :value="old('name.' . $lang, isset($category) ? $category->getTranslation('name', $lang, false) : null)"
        :placeholder="__('admin.name')"
    />

    <!-- sort -->
    <x-admin.field.number
        :name="'sort'"
        :value="old('sort', isset($category) ? $category->sort : 100)"
        :placeholder="__('admin.sort')"
    />

    <!-- active -->
    <x-admin.field.radio-switch
        class="m-0 me-auto"

        :name="'active'"
        :title="__('admin.show')"
        :checked="isset($category) ? $category->active : true"
    />
</div>
