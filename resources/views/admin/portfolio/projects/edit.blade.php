<x-admin.modal.content
    :size="'xl'"
    :title="__('admin.editing')"
>
    <x-slot:headerActions>
        <x-admin.button
            data-bs-dismiss="modal"
            :title="__('admin.cancel')"
            :btn="'btn-outline-danger btn-sm'"
        />
        <x-admin.button
            :type="'submit'"
            :form="'edit-project-control-form'"
            :withLoader="true"
            :title="__('admin.save')"
            :iconName="'floppy'"
            :btn="'btn-dark btn-sm'"
        />
    </x-slot:headerActions>
    <x-slot:body>
        <x-admin.control-form
            action="{{ route('admin.portfolio.project.update', $project->id) }}"
            id="edit-project-control-form"

            :method="'PATCH'"
            :isUpdateFromView="true"
            :updateIdSection="'projects-list'"
            data-keep-modal-open
        >
            @include('admin.portfolio.projects.fields')
        </x-admin.control-form>
    </x-slot:body>

    <x-slot:footer>
        <x-admin.button
            class="me-auto"
            data-bs-dismiss="modal"
            :title="__('admin.cancel')"
            :btn="'btn-danger'"
        />

        <x-admin.button
            :type="'submit'"
            :form="'edit-project-control-form'"
            :withLoader="true"
            :title="__('admin.save')"
            :iconName="'floppy'"
        />
    </x-slot:footer>
</x-admin.modal.content>
