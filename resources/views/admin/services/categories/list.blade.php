@foreach ($categories as $category)
    <div class="d-flex align-items-sm-center px-3 px-sm-4 py-2 border-bottom border-dark border-opacity-25">
        <div class="d-flex align-items-center col pe-0 pe-sm-3 gap-3 pe-3">
            <x-admin.picture
                style="width: 60px;"
                class="overflow-hidden"
                :src="$category->hasMedia($category->mediaHero)
                    ? $category->getFirstMediaUrl($category->mediaHero, 'sm-webp')
                    : null"
                :ratio="'1x1'"
                :fit="'contain'"
            />
            <div class="col fw-semibold">{{ $category->name }}</div>
        </div>
        <div class="col-auto d-flex align-items-center justify-content-end gap-3 mt-2 mt-sm-0">
            @if (!$category->active)
                <x-admin.icon
                    class="me-auto"
                    :name="'eye-slash'"
                    :width="'30'"
                    :height="'30'"
                />
            @endif

            <x-admin.ajax.delete-modal-button
                :subtitle="$category->name"
                :deleteAction="route('admin.services.category.delete', $category->id)"
                :updateIdSection="'categories-list'"
            />

            <x-admin.ajax.view-modal-button
                class="btn-sm p-2"
                :action="route('admin.services.category.edit', $category->id)"
                :modal_id="'category-control-modal'"
                :iconName="'pen'"
            />
        </div>
    </div>
@endforeach

@if ($categories->isEmpty())
    <x-admin.empty-message />
@endif
