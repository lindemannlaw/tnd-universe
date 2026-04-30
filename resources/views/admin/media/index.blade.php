@extends('admin.layouts.base')

@section('title', __('admin.media') . ' - ' . config('app.name'))

@section('panel')
    <x-admin.main-panel :title="__('admin.media')">
        @php
            $tableUrl = request()->fullUrlWithQuery(['view' => 'table']);
            $gridUrl  = request()->fullUrlWithQuery(['view' => 'grid']);
        @endphp

        <div class="btn-group" role="group" aria-label="{{ __('admin.media') }}">
            <a href="{{ $tableUrl }}"
               data-media-view="table"
               class="btn btn-sm d-flex align-items-center justify-content-center {{ $view === 'table' ? 'btn-secondary' : 'btn-outline-secondary' }}"
               title="{{ __('admin.list') }}">
                <x-admin.icon :name="'list-ul'" :width="20" :height="20" />
            </a>
            <a href="{{ $gridUrl }}"
               data-media-view="grid"
               class="btn btn-sm d-flex align-items-center justify-content-center {{ $view === 'grid' ? 'btn-secondary' : 'btn-outline-secondary' }}"
               title="{{ __('admin.gallery') }}">
                <x-admin.icon :name="'grid-3x3-gap'" :width="20" :height="20" />
            </a>
        </div>

        <x-admin.button
            class="btn-sm p-2"
            data-bs-toggle="modal"
            data-bs-target="#media-search-modal"
            :iconName="'search'"
        />
    </x-admin.main-panel>
@endsection

@section('content')
    <x-admin.container>
        <div
            id="media-list"
            class="d-flex flex-column flex-auto mx-n3 mx-sm-n4 mt-n4"
        >
            @include('admin.media.list')
        </div>
    </x-admin.container>
@endsection

@push('modals')
    <x-admin.modal-search
        :modalId="'media-search-modal'"
        :action="route('admin.media.index')"
        :title="__('admin.media')"
    />
    <x-admin.modal.wrapper id="media-detail-modal" />
@endpush

@push('footer-scripts')
<script>
    (function () {
        const STORAGE_KEY = 'admin.media.view';
        const url = new URL(window.location.href);
        const urlView = url.searchParams.get('view');
        const stored  = window.localStorage?.getItem(STORAGE_KEY);

        if (!urlView && stored && (stored === 'table' || stored === 'grid')) {
            url.searchParams.set('view', stored);
            window.location.replace(url.toString());
            return;
        }

        document.addEventListener('click', (event) => {
            const link = event.target.closest('[data-media-view]');
            if (!link) return;
            try { window.localStorage?.setItem(STORAGE_KEY, link.dataset.mediaView); } catch (_) {}
        });
    })();
</script>
@endpush
