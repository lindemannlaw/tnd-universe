@extends('admin.layouts.base')

@section('title', __('admin.home') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel
        :title="__('admin.home')"
    >
        <a href="{{ route('admin.seo-geo.show', ['type' => 'page', 'id' => $page->id]) }}"
           class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 me-2">
            <svg class="bi" width="13" height="13" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#stars"/></svg>
            SEO / GEO
        </a>
        <x-admin.button
            data-submit-loader
            :type="'submit'"
            :form="'controlForm'"
            :title="__('admin.save')"
            :iconName="'floppy'"
            :withLoader="true"
            :withMiniStyle="true"
        />
    </x-admin.main-panel>
@endsection

@section('content')
    <x-admin.container
        :id="'controlForm'"
        :action="route('admin.contacts.page.update', $page->id)"
        :method="'PATCH'"
    >
        <x-admin.tabs.wrapper>
            <x-slot:nav>
                <x-admin.tabs.nav-item
                    :is-active="true"
                    :target="'seo-pane'"
                    :title="__('admin.seo')"
                />

                <x-admin.tabs.nav-item
                    :target="'connection-pane'"
                    :title="__('admin.connection')"
                />

                <x-admin.tabs.nav-item
                    :target="'socials-pane'"
                    :title="__('admin.socials')"
                />

                <x-admin.tabs.nav-item
                    :target="'contact-email-pane'"
                    :title="__('admin.contact_email')"
                />
            </x-slot:nav>

            <x-slot:content>
                <x-admin.tabs.pane :is-active="true" :id="'seo-pane'">
                    @include('admin.contacts.tabs.seo')
                </x-admin.tabs.pane>

                <x-admin.tabs.pane :id="'connection-pane'">
                    @include('admin.contacts.tabs.connection')
                </x-admin.tabs.pane>

                <x-admin.tabs.pane :id="'socials-pane'">
                    @include('admin.contacts.tabs.socials')
                </x-admin.tabs.pane>

                <x-admin.tabs.pane :id="'contact-email-pane'">
                    @include('admin.contacts.tabs.contact-email')
                </x-admin.tabs.pane>
            </x-slot:content>
        </x-admin.tabs.wrapper>

    </x-admin.container>
@endsection
