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
        <a href="{{ route('admin.translations.index', ['type' => 'page', 'id' => $page->id]) }}"
           class="btn btn-sm btn-dark d-inline-flex align-items-center gap-1 me-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.913 17H20.087M12.913 17L11 21M12.913 17L15.7783 11.009C16.0092 10.5263 16.1246 10.2849 16.2826 10.2086C16.4199 10.1423 16.5801 10.1423 16.7174 10.2086C16.8754 10.2849 16.9908 10.5263 17.2217 11.009L20.087 17M20.087 17L22 21M2 5H8M8 5H11.5M8 5V3M11.5 5H14M11.5 5C11.0039 7.95729 9.85259 10.6362 8.16555 12.8844M10 14C9.38747 13.7248 8.76265 13.3421 8.16555 12.8844M8.16555 12.8844C6.81302 11.8478 5.60276 10.4266 5 9M8.16555 12.8844C6.56086 15.0229 4.47143 16.7718 2 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Übersetzungen
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
                    :title="'Hero / URL / Titel'"
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
