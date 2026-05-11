<div class="d-flex flex-column gap-4">
    <x-admin.field.text
        :name="'public_slug'"
        :value="old('public_slug', $page->public_slug)"
        :placeholder="'URL Slug (z. B. about)'"
        :required="false"
    />

    {{-- SEO / GEO fields are managed centrally on the "SEO / GEO" admin screen (single source of truth). --}}
</div>
