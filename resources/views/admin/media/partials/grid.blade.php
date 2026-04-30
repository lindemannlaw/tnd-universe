@php
    use Illuminate\Support\Number;

    $iconForMime = function (?string $mime): string {
        if (!$mime) return 'file-earmark';
        if (str_starts_with($mime, 'image/')) return 'file-earmark-image';
        if ($mime === 'application/pdf') return 'file-earmark-pdf';
        if (str_starts_with($mime, 'video/')) return 'file-earmark-play';
        if (str_starts_with($mime, 'audio/')) return 'file-earmark-music';
        if (str_contains($mime, 'zip') || str_contains($mime, 'compressed')) return 'file-earmark-zip';
        if (str_contains($mime, 'word')) return 'file-earmark-word';
        if (str_contains($mime, 'sheet') || str_contains($mime, 'excel')) return 'file-earmark-spreadsheet';
        return 'file-earmark';
    };
@endphp

<div class="d-flex flex-wrap align-items-center gap-2 px-3 px-sm-4 py-3 border-bottom border-dark border-opacity-25 bg-white">
    <span class="text-gray small me-2">{{ __('admin.sort') }}:</span>
    @foreach ($sortOptions as $key => $label)
        @php
            $isActive = $sortBy === $key;
            $nextDir  = ($isActive && $sortDir === 'asc') ? 'desc' : 'asc';
            $arrow    = $isActive ? ($sortDir === 'asc' ? 'arrow-up' : 'arrow-down') : null;
            $href     = request()->fullUrlWithQuery(['sort_by' => $key, 'sort_dir' => $nextDir]);
        @endphp
        <a href="{{ $href }}"
           class="btn btn-sm d-inline-flex align-items-center gap-1 {{ $isActive ? 'btn-secondary' : 'btn-outline-secondary' }}">
            <span>{{ $label }}</span>
            @if ($arrow)
                <x-admin.icon :name="$arrow" :width="14" :height="14" />
            @endif
        </a>
    @endforeach
</div>

<div class="p-3 p-sm-4">
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 g-3">
        @foreach ($media as $item)
            @php
                $isImage    = $item->mime_type && str_starts_with($item->mime_type, 'image/');
                $isOrphan   = $item->model === null;
                $ownerType  = $item->model_type ? class_basename($item->model_type) : null;
                $ownerLabel = $ownerType ? ($ownerLabels[$ownerType] ?? $ownerType) : null;
                $thumbUrl   = null;
                if ($isImage) {
                    try {
                        $thumbUrl = $item->getUrl('md-webp') ?: $item->getUrl();
                    } catch (\Throwable $e) {
                        $thumbUrl = $item->getUrl();
                    }
                }
                $deleteSubtitle = $item->file_name
                    . ($item->collection_name ? ' · ' . $item->collection_name : '')
                    . ($ownerLabel ? ' · ' . $ownerLabel : '');
            @endphp

            <div class="col">
                <div class="card h-100 shadow-sm position-relative {{ $isOrphan ? 'opacity-75' : '' }}">
                    @if (!empty($pickerMode))
                        <button type="button"
                                class="text-decoration-none text-reset d-block bg-transparent border-0 p-0 text-start"
                                data-picker-pick
                                data-media-id="{{ $item->id }}"
                                data-media-name="{{ $item->name }}"
                                data-media-file-name="{{ $item->file_name }}"
                                data-media-size="{{ $item->size }}"
                                data-media-mime="{{ $item->mime_type }}"
                                data-media-url="{{ $item->getUrl() }}">
                    @else
                    <a href="{{ route('admin.media.show', $item->id) }}"
                       data-ajax-view-modal-button
                       data-action="{{ route('admin.media.show', $item->id) }}"
                       data-modal="media-detail-modal"
                       class="text-decoration-none text-reset d-block">
                    @endif

                        <div class="ratio ratio-4x3 bg-light rounded-top overflow-hidden">
                            @if ($thumbUrl)
                                <img src="{{ $thumbUrl }}" alt="" style="object-fit: cover;">
                            @else
                                <div class="d-flex align-items-center justify-content-center">
                                    <x-admin.icon :name="$iconForMime($item->mime_type)" :width="56" :height="56" />
                                </div>
                            @endif
                        </div>

                        <div class="card-body p-2">
                            <div class="fw-semibold text-truncate" title="{{ $item->name }}">{{ $item->name }}</div>
                            <div class="text-gray small text-truncate" title="{{ $item->file_name }}">{{ $item->file_name }}</div>
                            <div class="d-flex justify-content-between text-gray small mt-1">
                                <span>{{ Number::fileSize($item->size) }}</span>
                                <span title="{{ $item->updated_at?->isoFormat('LLL') }}">{{ $item->updated_at?->diffForHumans() }}</span>
                            </div>
                            @if ($isOrphan)
                                <div class="mt-1"><span class="badge bg-warning text-dark">{{ __('admin.orphan') }}</span></div>
                            @endif
                        </div>

                    @if (!empty($pickerMode))
                        </button>
                    @else
                    </a>

                    <div class="position-absolute top-0 end-0 m-2 d-flex gap-1">
                        <x-admin.ajax.delete-modal-button
                            :subtitle="$deleteSubtitle"
                            :deleteAction="route('admin.media.delete', $item->id)"
                            :updateIdSection="'media-list'"
                        />
                    </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
