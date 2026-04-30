<div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">
        <div class="modal-header gap-2">
            <h1 class="modal-title fs-5 me-auto">{{ __('admin.upload') }}</h1>
            <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form
            id="library-upload-form"
            data-library-upload
            action="{{ route('admin.media.upload') }}"
            method="POST"
            enctype="multipart/form-data"
        >
            @csrf

            <div class="modal-body">
                <input type="file"
                       name="file"
                       class="form-control"
                       required>
                <div class="text-gray small mt-2">{{ __('admin.upload_library_hint') }}</div>
            </div>

            <div class="modal-footer">
                <x-admin.button
                    data-bs-dismiss="modal"
                    :title="__('admin.cancel')"
                    :btn="'btn-secondary'"
                />
                <x-admin.button
                    :type="'submit'"
                    :form="'library-upload-form'"
                    :iconName="'cloud-upload'"
                    :title="__('admin.upload')"
                    :withLoader="true"
                />
            </div>
        </form>
    </div>
</div>
