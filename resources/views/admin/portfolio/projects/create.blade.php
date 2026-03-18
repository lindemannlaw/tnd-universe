<x-admin.modal.content
    :size="'xl'"
    :title="__('admin.creating')"
>
    <x-slot:headerActions>
        <x-admin.button
            data-bs-dismiss="modal"
            :title="__('admin.cancel')"
            :btn="'btn-outline-danger btn-sm'"
        />
        <x-admin.button
            data-translate-blocks
            data-target-locale="de"
            data-translate-url="{{ route('admin.translate') }}"
            data-generate-seo-url="{{ route('admin.generate-seo') }}"
            :btn="'btn-outline-info btn-sm'"
            :iconName="'globe'"
            :title="'Auf Deutsch übersetzen'"
        />
        <x-admin.button
            :type="'submit'"
            :form="'create-project-control-form'"
            :withLoader="true"
            :title="__('admin.save')"
            :iconName="'floppy'"
            :btn="'btn-dark btn-sm'"
        />
    </x-slot:headerActions>

    <x-slot:body>
        <x-admin.control-form
            action="{{ route('admin.portfolio.project.store') }}"
            id="create-project-control-form"

            :isUpdateFromView="true"
            :updateIdSection="'projects-list'"
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
            :form="'create-project-control-form'"
            :withLoader="true"
            :title="__('admin.save')"
            :iconName="'floppy'"
        />
    </x-slot:footer>
</x-admin.modal.content>
