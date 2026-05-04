<div class="grid gap-4">
    <div class="d-flex flex-column gap-4 g-col-12 g-col-lg-5 g-col-xl-4">
        <!-- hero image -->
        <x-admin.field.image
            :name="'hero_image'"
            :placeholder="__('admin.hero_image') . ' ( 3 / 2 )'"
            :ratio="'3x2'"
            :src="!(isset($isClone) && $isClone) && isset($project) && $project->hasMedia($project->mediaHero)
                ? $project->getFirstMediaUrl($project->mediaHero, 'md-webp')
                : null"
            :required="!(isset($isClone) && $isClone) && isset($project)
                ? !$project->hasMedia($project->mediaHero)
                : true"
        />
    </div>

    <div class="d-flex flex-column gap-4 g-col-12 g-col-lg-7 g-col-xl-8">
        @php $lang = config('app.fallback_locale', 'en'); @endphp
        <div class="d-flex flex-column gap-4">
            <!-- slug -->
            <x-admin.field.text
                :name="'slug'"
                :value="old('slug', isset($project) ? $project->slug : null)"
                :placeholder="'Slug (z. B. zuerich-residence-clone)'"
                :required="false"
            />

            <!-- title -->
            <x-admin.field.text
                :name="'title[' . $lang . ']'"
                :value="old('title.' . $lang, isset($project) ? $project->getTranslation('title', $lang) : null)"
                :placeholder="__('admin.title')"
            />

            <!-- short description -->
            <x-admin.field.textarea
                :name="'short_description[' . $lang . ']'"
                :value="old('short_description.' . $lang, isset($project) ? $project->getTranslation('short_description', $lang) : null)"
                :placeholder="__('admin.short_description')"
            />

            <!-- location -->
            <x-admin.field.text
                :name="'location[' . $lang . ']'"
                :value="old('location.' . $lang, isset($project) ? $project->getTranslation('location', $lang) : null)"
                :placeholder="__('admin.location')"
            />
        </div>

        <!-- area -->
        <x-admin.field.number
            :name="'area'"
            :value="old('area', isset($project) ? $project->area : null)"
            :placeholder="__('admin.area') . ' (m²)'"
        />

        <!-- sort -->
        <x-admin.field.number
            :name="'sort'"
            :value="old('sort', isset($project) ? $project->sort : 1000)"
            :placeholder="__('admin.sort')"
        />

        <!-- active -->
        <x-admin.field.radio-switch
            class="m-0 me-auto"
            :name="'active'"
            :title="__('admin.show')"
            :checked="isset($project) ? $project->active : true"
        />
    </div>
</div>
