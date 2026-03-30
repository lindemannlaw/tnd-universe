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
        <a href="{{ route('admin.seo-geo.show', ['type' => 'project', 'id' => $project->id]) }}"
           target="_blank"
           class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
            <svg class="bi" width="13" height="13" fill="currentColor"><use xlink:href="/img/icons/bootstrap-icons.svg#stars"/></svg>
            SEO / GEO
        </a>
        <x-admin.button
            data-generate-seo
            data-generate-seo-url="{{ route('admin.generate-seo') }}"
            data-update-timestamps-url="{{ route('admin.portfolio.project.update-timestamps', $project) }}"
            data-text-timestamps="{{ json_encode($project->text_timestamps ?? [], JSON_UNESCAPED_UNICODE) }}"
            :btn="'btn-outline-success btn-sm'"
            :iconName="'stars'"
            :title="'SEO generieren'"
        />
        <button type="button"
            data-translate-blocks
            data-target-locale="de"
            data-translate-url="{{ route('admin.translate') }}"
            data-apply-translations-url="{{ route('admin.portfolio.project.apply-translations', $project) }}"
            data-update-timestamps-url="{{ route('admin.portfolio.project.update-timestamps', $project) }}"
            data-text-timestamps="{{ json_encode($project->text_timestamps ?? [], JSON_UNESCAPED_UNICODE) }}"
            class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12.913 17H20.087M12.913 17L11 21M12.913 17L15.7783 11.009C16.0092 10.5263 16.1246 10.2849 16.2826 10.2086C16.4199 10.1423 16.5801 10.1423 16.7174 10.2086C16.8754 10.2849 16.9908 10.5263 17.2217 11.009L20.087 17M20.087 17L22 21M2 5H8M8 5H11.5M8 5V3M11.5 5H14M11.5 5C11.0039 7.95729 9.85259 10.6362 8.16555 12.8844M10 14C9.38747 13.7248 8.76265 13.3421 8.16555 12.8844M8.16555 12.8844C6.81302 11.8478 5.60276 10.4266 5 9M8.16555 12.8844C6.56086 15.0229 4.47143 16.7718 2 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Übersetzungen</span>
        </button>
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
