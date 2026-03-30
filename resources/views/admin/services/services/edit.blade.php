<x-admin.modal.content
    :size="'xl'"
    :title="__('admin.editing')"
>
    <x-slot:body>
        <x-admin.control-form
            action="{{ route('admin.services.service.update', $service->id) }}"
            id="edit-service-control-form"

            :method="'PATCH'"
            :isUpdateFromView="true"
            :updateIdSection="'services-list'"
        >
            @include('admin.services.services.fields')
        </x-admin.control-form>
    </x-slot:body>

    <x-slot:footer>
        <x-admin.button
            class="me-auto"
            data-bs-dismiss="modal"
            :title="__('admin.cancel')"
            :btn="'btn-danger'"
        />

        <a href="{{ route('admin.seo-geo.show', ['type' => 'service', 'id' => $service->id]) }}"
           target="_blank"
           class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <svg class="bi" width="13" height="13" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#stars"/></svg>
            SEO / GEO
        </a>

        <x-admin.button
            :type="'submit'"
            :form="'edit-service-control-form'"
            :withLoader="true"
            :title="__('admin.save')"
            :iconName="'floppy'"
        />
    </x-slot:footer>
</x-admin.modal.content>
