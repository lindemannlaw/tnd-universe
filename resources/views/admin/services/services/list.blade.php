@foreach ($services as $service)
    <div
        class="d-flex flex-column flex-sm-row align-items-sm-center px-3 px-sm-4 py-2 border-bottom border-dark border-opacity-25">
        <div class="d-flex align-items-center col-12 col-sm pe-0 pe-sm-3 gap-3">
            <x-admin.picture
                style="width: 80px;"
                class="overflow-hidden"
                :src="$service->hasMedia($service->mediaHero)
                    ? $service->getFirstMediaUrl($service->mediaHero, 'sm-webp')
                    : null"
                :ratio="'3x2'"
                :fit="'contain'"
            />
            <div class="col">
                <div class="fw-semibold">{{ $service->title }}</div>
                @if ($service->description)
                    <div
                        style="font-size: 14px;"
                        class="line-clamp-1 text-gray"
                    >{!! $service->description !!}</div>
                @endif
            </div>
        </div>
        <div class="col-12 col-sm-auto d-flex align-items-center justify-content-end gap-3 mt-2 mt-sm-0">
            <div class="me-auto pe-1">
                <div
                    style="max-width: 150px; font-size: 14px;"
                    class="text-gray"
                >{{ $service->category->name }}</div>
            </div>

            @if (!$service->active)
                <x-admin.icon
                    :name="'eye-slash'"
                    :width="'30'"
                    :height="'30'"
                />
            @endif

            <x-admin.ajax.delete-modal-button
                :subtitle="$service->title"
                :deleteAction="route('admin.services.service.delete', $service->id)"
                :updateIdSection="'services-list'"
            />

            <x-admin.ajax.view-modal-button
                class="btn-sm p-2"
                :action="route('admin.services.service.edit', $service->id)"
                :modal_id="'service-control-modal'"
                :iconName="'pen'"
            />
        </div>
    </div>
@endforeach

@if ($services->isEmpty())
    <x-admin.empty-message />
@endif
