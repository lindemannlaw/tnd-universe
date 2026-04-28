@php $lang = config('app.fallback_locale', 'en'); @endphp

<div class="d-flex flex-column gap-4">
    <!-- title -->
    <x-admin.field.text
        :name="'title['. $lang .']'"
        :value="old('title.' . $lang, isset($article) ? $article->getTranslation('title', $lang, false) : null)"
        :placeholder="__('admin.title')"
    />

    <!-- short description -->
    <x-admin.field.text
        :name="'short_description['. $lang .']'"
        :value="old('short_description.' . $lang, isset($article) ? $article->getTranslation('short_description', $lang, false) : null)"
        :placeholder="__('admin.short_description')"
        :required="false"
    />

    <!-- description -->
    <x-admin.field.wysiwyg
        :name="'description['. $lang .']'"
        :value="old('description.' . $lang, isset($article) ? $article->getTranslation('description', $lang, false) : null)"
        :placeholder="__('admin.description')"
        :buttons="'blockquote|list|image'"
    />

    <!-- seo title -->
    <x-admin.field.text
        :name="'seo_title['. $lang .']'"
        :value="old('seo_title.' . $lang, isset($article) ? $article->getTranslation('seo_title', $lang, false) : null)"
        :placeholder="__('admin.seo_title')"
        :required="false"
    />

    <!-- seo description -->
    <x-admin.field.text
        :name="'seo_description['. $lang .']'"
        :value="old('seo_description.' . $lang, isset($article) ? $article->getTranslation('seo_description', $lang, false) : null)"
        :placeholder="__('admin.seo_description')"
        :required="false"
    />

    <!-- seo keywords -->
    <x-admin.field.text
        :name="'seo_keywords['. $lang .']'"
        :value="old('seo_keywords.' . $lang, isset($article) ? $article->getTranslation('seo_keywords', $lang, false) : null)"
        :placeholder="__('admin.seo_keywords')"
        :required="false"
    />

    <!-- geo text -->
    <x-admin.field.textarea
        :name="'geo_text['. $lang .']'"
        :value="old('geo_text.' . $lang, isset($article) ? $article->getTranslation('geo_text', $lang, false) : null)"
        :placeholder="__('admin.geo_text')"
        :required="false"
    />

    <!-- category -->
    <x-admin.field.select
        :placeholder="__('admin.category')"
        :name="'category_id'"
    >
        @foreach($categories as $category)
            <option {{ isset($article) && $article->first_category?->id === $category->id ? 'selected' : null }} value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
    </x-admin.field.select>

    <!-- sort -->
    <x-admin.field.number
        :name="'sort'"
        :value="old('sort', isset($article) ? $article->sort : 10000)"
        :placeholder="__('admin.sort')"
    />

    <!-- active -->
    <x-admin.field.radio-switch
        class="m-0 me-auto"

        :name="'active'"
        :title="__('admin.show')"
        :checked="isset($article) ? $article->active : true"
    />
</div>
