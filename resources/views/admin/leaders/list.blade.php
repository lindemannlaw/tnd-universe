@foreach ($leaders as $leader)
    <div
        class="d-flex flex-column g-col-12 g-col-sm-6 g-col-md-4 g-col-xxl-3 rounded border border-dark border-opacity-10 shadow">
        <div class="d-flex flex-column gap-1 p-3">
            <x-admin.picture
                :fit="'contain'"
                :position="'center '"
                :src="$leader->hasMedia($leader->mediaPhoto)
                    ? $leader->getFirstMediaUrl($leader->mediaPhoto, 'md-webp')
                    : null"
                :ratio="'1x1'"
            />

            <div class="fw-semibold">{{ $leader->name }}</div>
            <div
                style="font-size: 14px;"
                class="line-clamp-1 text-gray"
            >{{ $leader->position }}</div>
        </div>
        <div
            class="d-flex align-items-center justify-content-end gap-3 p-3 mt-auto border-top border-dark border-opacity-25">
            @if (!$leader->active)
                <x-admin.icon
                    :name="'eye-slash'"
                    :width="'30'"
                    :height="'30'"
                />
            @endif

            <x-admin.ajax.delete-modal-button
                :subtitle="$leader->title"
                :deleteAction="route('admin.about.leader.delete', $leader->id)"
                :updateIdSection="'projects-list'"
            />

            <x-admin.ajax.view-modal-button
                class="btn-sm p-2"
                :action="route('admin.about.leader.edit', $leader->id)"
                :modal_id="'leader-control-modal'"
                :iconName="'pen'"
            />
        </div>
    </div>
@endforeach

@if ($leaders->isEmpty())
    <div class="g-col-12 my-auto">
        <x-admin.empty-message />
    </div>
@endif
