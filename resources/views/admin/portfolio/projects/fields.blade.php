<x-admin.tabs.wrapper>
    <x-slot:nav>
        <x-admin.tabs.nav-item
            :is-active="true"
            :target="'main-section'"
            :title="__('admin.main')"
        />

        <x-admin.tabs.nav-item
            :target="'gallery-section'"
            :title="__('admin.gallery')"
        />

        <x-admin.tabs.nav-item
            :target="'property-details-section'"
            :title="__('base.property_details')"
        />

        <x-admin.tabs.nav-item
            :target="'description-section'"
            :title="__('admin.description')"
        />

        <x-admin.tabs.nav-item
            :target="'files-section'"
            :title="__('admin.files')"
        />

        <x-admin.tabs.nav-item
            :target="'seo-section'"
            :title="__('admin.seo')"
        />
    </x-slot:nav>

    <x-slot:content>
        <x-admin.tabs.pane
            :is-active="true"
            :id="'main-section'"
        >
            @include('admin.portfolio.projects.tabs.main')
        </x-admin.tabs.pane>

        <x-admin.tabs.pane :id="'gallery-section'">
            @include('admin.portfolio.projects.tabs.gallery')
        </x-admin.tabs.pane>

        <x-admin.tabs.pane :id="'property-details-section'">
            @include('admin.portfolio.projects.tabs.property-details')
        </x-admin.tabs.pane>

        <x-admin.tabs.pane :id="'description-section'">
            @include('admin.portfolio.projects.tabs.description')
        </x-admin.tabs.pane>

        <x-admin.tabs.pane :id="'files-section'">
            @include('admin.portfolio.projects.tabs.files')
        </x-admin.tabs.pane>

        <x-admin.tabs.pane :id="'seo-section'">
            @include('admin.portfolio.projects.tabs.seo')
        </x-admin.tabs.pane>
    </x-slot:content>
</x-admin.tabs.wrapper>
