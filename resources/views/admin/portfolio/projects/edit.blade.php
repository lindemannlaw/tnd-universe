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
            data-generate-seo
            data-generate-seo-url="{{ route('admin.generate-seo') }}"
            data-update-timestamps-url="{{ route('admin.portfolio.project.update-timestamps', $project) }}"
            data-text-timestamps="{{ json_encode($project->text_timestamps ?? [], JSON_UNESCAPED_UNICODE) }}"
            :btn="'btn-outline-secondary btn-sm'"
            :iconName="'stars'"
            :title="'SEO generieren'"
        />
        <x-admin.button
            data-translate-blocks
            data-target-locale="de"
            data-translate-url="{{ route('admin.translate') }}"
            data-update-timestamps-url="{{ route('admin.portfolio.project.update-timestamps', $project) }}"
            data-text-timestamps="{{ json_encode($project->text_timestamps ?? [], JSON_UNESCAPED_UNICODE) }}"
            :btn="'btn-outline-info btn-sm'"
            :iconName="'globe'"
            :shortTitle="'Übersetze…'"
            :longTitle="'Auf Deutsch übersetzen'"
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
            data-modal-refresh-url="{{ route('admin.portfolio.project.edit', $project->id) }}"
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
