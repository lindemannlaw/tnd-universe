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

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width: 80px;">&nbsp;</th>
                <th>
                    <a href="{{ $sortUrl('name') }}" class="d-inline-flex align-items-center gap-1 text-reset text-decoration-none">
                        {{ __('admin.name') }}
                        @if ($arrow = $sortArrow('name'))
                            <x-admin.icon :name="$arrow" :width="14" :height="14" />
                        @endif
                    </a>
                </th>
                <th>{{ __('admin.owner') }}</th>
                <th>
                    <a href="{{ $sortUrl('size') }}" class="d-inline-flex align-items-center gap-1 text-reset text-decoration-none">
                        {{ __('admin.size') }}
                        @if ($arrow = $sortArrow('size'))
                            <x-admin.icon :name="$arrow" :width="14" :height="14" />
                        @endif
                    </a>
                </th>
                <th>
                    <a href="{{ $sortUrl('updated_at') }}" class="d-inline-flex align-items-center gap-1 text-reset text-decoration-none">
                        {{ __('admin.last_modified') }}
                        @if ($arrow = $sortArrow('updated_at'))
                            <x-admin.icon :name="$arrow" :width="14" :height="14" />
                        @endif
                    </a>
                </th>
                <th class="text-end" style="width: 1%; white-space: nowrap;">&nbsp;</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($media as $item)
                @php
                    $isImage    = $item->mime_type && str_starts_with($item->mime_type, 'image/');
                    $isOrphan   = $item->model === null;
                    $ownerType  = $item->model_type ? class_basename($item->model_type) : null;
                    $ownerLabel = $ownerType ? ($ownerLabels[$ownerType] ?? $ownerType) : null;
                    $thumbUrl   = null;
                    if ($isImage) {
                        try {
                            $thumbUrl = $item->getUrl('sm-webp') ?: $item->getUrl();
                        } catch (\Throwable $e) {
                            $thumbUrl = $item->getUrl();
                        }
                    }
                    $deleteSubtitle = $item->file_name
                        . ($item->collection_name ? ' · ' . $item->collection_name : '')
                        . ($ownerLabel ? ' · ' . $ownerLabel : '');
                @endphp

                <tr>
                    <td>
                        @if ($thumbUrl)
                            <img src="{{ $thumbUrl }}" alt="" class="rounded"
                                 style="width: 64px; height: 48px; object-fit: cover; background: #f1f1f1;">
                        @else
                            <div class="rounded d-flex align-items-center justify-content-center bg-light"
                                 style="width: 64px; height: 48px;">
                                <x-admin.icon :name="$iconForMime($item->mime_type)" :width="28" :height="28" />
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $item->name }}</div>
                        <div class="text-gray small text-break">{{ $item->file_name }}</div>
                    </td>
                    <td>
                        @php
                            // Build the full usage list: native Spatie owner + every
                            // model_media pivot attachment, deduplicated. The mirror
                            // migration already echoes non-User-owned natives into the
                            // pivot, so the dedupe collapses those into one entry.
                            $usages = [];
                            if (! $isOrphan && $item->model_type) {
                                $usages[] = ['type' => $item->model_type, 'collection' => $item->collection_name];
                            }
                            foreach ($item->model_media_attachments ?? [] as $att) {
                                $usages[] = ['type' => $att->model_type, 'collection' => $att->collection_name];
                            }
                            $seen = [];
                            $usages = array_values(array_filter($usages, function ($u) use (&$seen) {
                                $k = ($u['type'] ?? '') . '|' . ($u['collection'] ?? '');
                                if (isset($seen[$k])) return false;
                                $seen[$k] = true;
                                return true;
                            }));
                        @endphp
                        @if (empty($usages))
                            <span class="badge bg-warning text-dark">{{ __('admin.orphan') }}</span>
                        @else
                            @foreach ($usages as $u)
                                @php
                                    $t     = class_basename($u['type']);
                                    $label = $ownerLabels[$t] ?? $t;
                                @endphp
                                <div>
                                    {{ $label }}
                                    @if ($u['collection'])
                                        <span class="text-gray small">/ {{ $u['collection'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </td>
                    <td class="text-nowrap">{{ Number::fileSize($item->size) }}</td>
                    <td class="text-nowrap" title="{{ $item->updated_at?->isoFormat('LLL') }}">
                        {{ $item->updated_at?->diffForHumans() }}
                    </td>
                    <td class="text-end">
                        @if (!empty($pickerMode))
                            <button type="button"
                                    class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2"
                                    data-picker-pick
                                    data-media-id="{{ $item->id }}"
                                    data-media-name="{{ $item->name }}"
                                    data-media-file-name="{{ $item->file_name }}"
                                    data-media-size="{{ $item->size }}"
                                    data-media-mime="{{ $item->mime_type }}"
                                    data-media-url="{{ $item->getUrl() }}">
                                <x-admin.icon :name="'check2'" :width="14" :height="14" />
                                <span>{{ __('admin.pick') }}</span>
                            </button>
                        @else
                        <div class="d-inline-flex align-items-center gap-2">
                            <x-admin.ajax.view-modal-button
                                class="btn-sm p-2"
                                :action="route('admin.media.show', $item->id)"
                                :modal_id="'media-detail-modal'"
                                :iconName="'eye'"
                            />

                            <x-admin.button
                                class="btn-sm p-2"
                                :href="route('admin.media.download', $item->id)"
                                :iconName="'download'"
                                :btn="'btn-outline-secondary'"
                                title="{{ __('admin.download') }}"
                            />

                            <x-admin.ajax.delete-modal-button
                                :subtitle="$deleteSubtitle"
                                :deleteAction="route('admin.media.delete', $item->id)"
                                :updateIdSection="'media-list'"
                            />
                        </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
