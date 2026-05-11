<x-admin.tabs.wrapper>
    <x-slot:nav>
        <x-admin.tabs.nav-item
            :is-active="true"
            :target="'main-section'"
            :title="__('admin.main')"
        />

        <x-admin.tabs.nav-item
            :target="'details-section'"
            :title="__('admin.details')"
        />

        {{--<x-admin.tabs.nav-item
            :target="'info-section'"
            :title="__('admin.info')"
        />--}}
    </x-slot:nav>

    <x-slot:content>
        <x-admin.tabs.pane :is-active="true" :id="'main-section'">
            @include('admin.services.services.tabs.main')
        </x-admin.tabs.pane>

        <x-admin.tabs.pane :id="'details-section'">
            @include('admin.services.services.tabs.details')
        </x-admin.tabs.pane>

        {{--<x-admin.tabs.pane :id="'info-section'">
            @include('admin.services.services.tabs.info')
        </x-admin.tabs.pane>--}}
    </x-slot:content>
</x-admin.tabs.wrapper>
