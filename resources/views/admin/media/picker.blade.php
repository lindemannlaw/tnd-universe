@php
    $pickerMode = true;
    $field      = $field ?? null;
    $mimeFilter = $mimeFilter ?? null;
@endphp

<div class="modal-dialog modal-fullscreen" data-media-picker-modal data-field="{{ $field }}">
    <div class="modal-content">
        <div class="modal-header gap-2">
            <h1 class="modal-title fs-5 me-auto">{{ __('admin.media') }}</h1>
            <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body" style="overflow-y: auto;">
            <div class="d-flex flex-column gap-3">
                <form
                    data-picker-upload
                    action="{{ route('admin.media.upload') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="d-flex gap-2 align-items-center"
                >
                    @csrf
                    <input type="file"
                           name="file"
                           class="form-control form-control-sm"
                           required
                           {{ $mimeFilter ? 'accept="'.$mimeFilter.'"' : '' }}>
                    <x-admin.button
                        :type="'submit'"
                        :iconName="'cloud-upload'"
                        :title="__('admin.upload')"
                        :withLoader="true"
                    />
                </form>

                <form
                    data-picker-search
                    action="{{ route('admin.media.picker.list') }}"
                    method="GET"
                    class="d-flex gap-2 align-items-center"
                >
                    <input type="hidden" name="mime_filter" value="{{ $mimeFilter }}">
                    <input type="hidden" name="field" value="{{ $field }}">
                    <input type="text"
                           name="search_query"
                           value="{{ $query }}"
                           class="form-control form-control-sm"
                           placeholder="{{ __('fields.query') }}">
                    <x-admin.button
                        :type="'submit'"
                        :btn="'btn-outline-secondary'"
                        :iconName="'search'"
                        class="btn-sm"
                    />
                </form>

                <div id="media-picker-list" class="border rounded">
                    @include('admin.media.picker-list')
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <x-admin.button
                data-bs-dismiss="modal"
                :title="__('admin.cancel')"
                :btn="'btn-secondary'"
            />
        </div>
    </div>
</div>
