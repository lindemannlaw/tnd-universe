@foreach ($projects as $project)
    <div
        class="d-flex flex-column flex-sm-row align-items-sm-center px-3 px-sm-4 py-2 border-bottom border-dark border-opacity-25">
        <div class="d-flex align-items-center col-12 col-sm pe-0 pe-sm-3 gap-3">
            <x-admin.picture
                style="width: 80px;"
                class="rounded overflow-hidden"
                :src="$project->hasMedia($project->mediaHero)
                    ? $project->getFirstMediaUrl($project->mediaHero, 'sm-webp')
                    : null"
                :ratio="'3x2'"
            />
            <div class="col">
                <div class="fw-semibold">{{ $project->title }}</div>
                @php
                    $shortDesc = $project->getTranslation('short_description', app()->getLocale(), false)
                        ?: $project->getTranslation('short_description', config('app.fallback_locale'), false);
                @endphp
                @if ($shortDesc)
                    <div
                        style="font-size: 14px;"
                        class="line-clamp-1 text-gray"
                    >{{ strip_tags($shortDesc) }}</div>
                @endif
            </div>
        </div>
        <div class="col-12 col-sm-auto d-flex align-items-center justify-content-end gap-3 mt-2 mt-sm-0">
            @if (!$project->active)
                <x-admin.icon
                    :name="'eye-slash'"
                    :width="'30'"
                    :height="'30'"
                />
            @endif

            <x-admin.ajax.delete-modal-button
                :subtitle="$project->title"
                :deleteAction="route('admin.portfolio.project.delete', $project->id)"
                :updateIdSection="'projects-list'"
            />

            <x-admin.button
                class="btn-sm p-2"
                :btn="'btn-outline-secondary'"
                :iconName="'copy'"
                :withLoader="true"
                data-clone-project
                data-clone-url="{{ route('admin.portfolio.project.clone', $project->id) }}"
                data-update-id-section="projects-list"
                data-clone-confirm="Projekt duplizieren?"
                title="Duplizieren"
            />

            <x-admin.ajax.view-modal-button
                class="btn-sm p-2"
                :action="route('admin.portfolio.project.edit', $project->id)"
                :modal_id="'project-control-modal'"
                :iconName="'pen'"
            />
        </div>
    </div>
@endforeach

@if ($projects->isEmpty())
    <x-admin.empty-message />
@endif
