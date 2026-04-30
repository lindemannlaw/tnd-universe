@php
    use Illuminate\Support\Number;

    $isImage  = $media->mime_type && str_starts_with($media->mime_type, 'image/');
    $isOrphan = $owner === null;

    $ownerLabels = [
        'Project'         => __('admin.project'),
        'NewsArticle'     => __('admin.articles'),
        'Service'         => __('admin.service'),
        'ServiceCategory' => __('admin.category'),
        'Page'            => __('admin.page'),
        'SiteSection'     => __('admin.sections'),
        'Leader'          => __('admin.leader'),
        'User'            => __('admin.profile'),
    ];
    $ownerType  = $media->model_type ? class_basename($media->model_type) : null;
    $ownerLabel = $ownerType ? ($ownerLabels[$ownerType] ?? $ownerType) : null;

    $iconForMime = function (?string $mime): string {
        if (!$mime) return 'file-earmark';
        if ($mime === 'application/pdf') return 'file-earmark-pdf';
        if (str_starts_with($mime ?? '', 'video/')) return 'file-earmark-play';
        if (str_starts_with($mime ?? '', 'audio/')) return 'file-earmark-music';
        if (str_contains($mime, 'zip') || str_contains($mime, 'compressed')) return 'file-earmark-zip';
        if (str_contains($mime, 'word')) return 'file-earmark-word';
        if (str_contains($mime, 'sheet') || str_contains($mime, 'excel')) return 'file-earmark-spreadsheet';
        return 'file-earmark';
    };

    $previewUrl = null;
    if ($isImage) {
        try {
            $previewUrl = $media->getUrl('lg-webp') ?: $media->getUrl();
        } catch (\Throwable $e) {
            $previewUrl = $media->getUrl();
        }
    }

    $deleteSubtitle = $media->file_name
        . ($media->collection_name ? ' · ' . $media->collection_name : '')
        . ($ownerLabel ? ' · ' . $ownerLabel : '');

    // Generated conversions (Spatie xl/hd/lg/md/sm-webp etc.) — pull from
    // the `generated_conversions` JSON column, resolve URL + on-disk size.
    $conversions = collect($media->generated_conversions ?? [])
        ->filter(fn ($v) => (bool) $v)
        ->map(function ($_, $name) use ($media) {
            $size = null;
            try {
                $path = $media->getPath($name);
                if (is_file($path)) {
                    $size = filesize($path);
                }
            } catch (\Throwable $e) {
                // disk path unresolvable — leave size as null
            }
            $url = null;
            try {
                $url = $media->getUrl($name);
            } catch (\Throwable $e) {
                // skip
            }
            return ['name' => $name, 'url' => $url, 'size' => $size];
        })
        ->filter(fn ($c) => !empty($c['url']))
        ->values();
@endphp

<x-admin.modal.content :size="'lg'" :title="$media->name">
    <x-slot:body>
        <div class="d-flex flex-column gap-3">
            <div class="bg-light rounded p-2 d-flex align-items-center justify-content-center"
                 style="min-height: 200px; max-height: 50vh; overflow: hidden;">
                @if ($previewUrl)
                    <img src="{{ $previewUrl }}" alt="{{ $media->name }}"
                         style="max-width: 100%; max-height: 50vh; object-fit: contain;">
                @else
                    <div class="d-flex flex-column align-items-center gap-2 text-gray py-4">
                        <x-admin.icon :name="$iconForMime($media->mime_type)" :width="64" :height="64" />
                        <span class="small">{{ $media->mime_type ?? '—' }}</span>
                    </div>
                @endif
            </div>

            <table class="table table-sm mb-0">
                <tbody>
                    <tr>
                        <th class="text-gray fw-normal" style="width: 30%;">{{ __('admin.file_name') }}</th>
                        <td class="text-break">{{ $media->file_name }}</td>
                    </tr>
                    <tr>
                        <th class="text-gray fw-normal">{{ __('admin.size') }}</th>
                        <td>{{ Number::fileSize($media->size) }}</td>
                    </tr>
                    <tr>
                        <th class="text-gray fw-normal">MIME</th>
                        <td>{{ $media->mime_type ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th class="text-gray fw-normal">{{ __('admin.collection') }}</th>
                        <td>{{ $media->collection_name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th class="text-gray fw-normal">{{ __('admin.owner') }}</th>
                        <td>
                            @if ($isOrphan)
                                <span class="badge bg-warning text-dark">{{ __('admin.orphan') }}</span>
                            @else
                                {{ $ownerLabel }}
                                <span class="text-gray">#{{ $media->model_id }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-gray fw-normal">{{ __('admin.last_modified') }}</th>
                        <td>{{ $media->updated_at?->isoFormat('LLL') }}</td>
                    </tr>
                </tbody>
            </table>

            @if ($conversions->isNotEmpty())
                <div class="border-top pt-3">
                    <div class="fw-semibold small text-gray mb-2">
                        {{ __('admin.conversions') }}
                        <span class="text-gray">({{ $conversions->count() }})</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('admin.name') }}</th>
                                    <th>{{ __('admin.size') }}</th>
                                    <th class="text-end" style="width: 1%; white-space: nowrap;">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($conversions as $c)
                                    <tr>
                                        <td><code>{{ $c['name'] }}</code></td>
                                        <td class="text-nowrap">
                                            {{ $c['size'] !== null ? Number::fileSize($c['size']) : '—' }}
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ $c['url'] }}"
                                               target="_blank"
                                               rel="noopener"
                                               class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2">
                                                <x-admin.icon :name="'box-arrow-up-right'" :width="14" :height="14" />
                                                {{ __('admin.open') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @unless ($isOrphan)
                <form
                    id="media-replace-form"
                    action="{{ route('admin.media.replace', $media->id) }}"
                    method="POST"
                    enctype="multipart/form-data"
                    data-ajax-with-update-from-view
                    data-update-id-section="media-list"
                    class="d-flex flex-column gap-2 border-top pt-3"
                >
                    @csrf

                    <label class="fw-semibold small text-gray">{{ __('admin.replace_file') }}</label>
                    <div class="d-flex gap-2 align-items-stretch">
                        <input
                            type="file"
                            name="file"
                            class="form-control"
                            required
                        />
                        <x-admin.button
                            :type="'submit'"
                            :form="'media-replace-form'"
                            :iconName="'arrow-repeat'"
                            :title="__('admin.replace')"
                            :withLoader="true"
                        />
                    </div>
                    @if ($media->mime_type)
                        <div class="text-gray small">
                            {{ __('admin.replace_hint') }}
                        </div>
                    @endif
                </form>
            @endunless
        </div>
    </x-slot:body>

    <x-slot:footer>
        <x-admin.button
            class="me-auto"
            data-bs-dismiss="modal"
            :title="__('admin.close')"
            :btn="'btn-secondary'"
        />

        <x-admin.button
            :href="route('admin.media.download', $media->id)"
            :iconName="'download'"
            :title="__('admin.download')"
            :btn="'btn-primary'"
        />

        <x-admin.ajax.delete-modal-button
            :subtitle="$deleteSubtitle"
            :deleteAction="route('admin.media.delete', $media->id)"
            :updateIdSection="'media-list'"
        />
    </x-slot:footer>
</x-admin.modal.content>
