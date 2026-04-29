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
            data-generate-seo
            data-generate-seo-url="{{ route('admin.generate-seo') }}"
            :btn="'btn-outline-success btn-sm'"
            :iconName="'stars'"
            :title="'SEO generieren'"
        />
        <a href="{{ route('admin.translations.index') }}"
           target="_blank"
           class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1"
           title="Übersetzungen-Dashboard öffnen">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12.913 17H20.087M12.913 17L11 21M12.913 17L15.7783 11.009C16.0092 10.5263 16.1246 10.2849 16.2826 10.2086C16.4199 10.1423 16.5801 10.1423 16.7174 10.2086C16.8754 10.2849 16.9908 10.5263 17.2217 11.009L20.087 17M20.087 17L22 21M2 5H8M8 5H11.5M8 5V3M11.5 5H14M11.5 5C11.0039 7.95729 9.85259 10.6362 8.16555 12.8844M10 14C9.38747 13.7248 8.76265 13.3421 8.16555 12.8844M8.16555 12.8844C6.81302 11.8478 5.60276 10.4266 5 9M8.16555 12.8844C6.56086 15.0229 4.47143 16.7718 2 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Übersetzungen</span>
        </a>
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
