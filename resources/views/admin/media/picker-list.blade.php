@php
    $pickerMode = true;
    $field      = $field ?? null;
    $mimeFilter = $mimeFilter ?? null;

    $ownerLabels = [
        'Project'         => __('admin.project'),
        'NewsArticle'     => __('admin.articles'),
        'Service'         => __('admin.service'),
        'ServiceCategory' => __('admin.category'),
        'Page'            => __('admin.page'),
        'SiteSection'     => __('admin.sections'),
        'Leader'          => __('admin.leader'),
        'User'            => __('admin.library'),
    ];

    $sortOptions = [
        'name'       => __('admin.name'),
        'size'       => __('admin.size'),
        'updated_at' => __('admin.last_modified'),
    ];

    $sortUrl = function (string $key) use ($sortBy, $sortDir, $mimeFilter, $field, $query) {
        $nextDir = ($sortBy === $key && $sortDir === 'asc') ? 'desc' : 'asc';
        return route('admin.media.picker.list', [
            'sort_by'      => $key,
            'sort_dir'     => $nextDir,
            'mime_filter'  => $mimeFilter,
            'field'        => $field,
            'search_query' => $query,
        ]);
    };

    $sortArrow = function (string $key) use ($sortBy, $sortDir) {
        if ($sortBy !== $key) return null;
        return $sortDir === 'asc' ? 'arrow-up' : 'arrow-down';
    };
@endphp

@if ($media->isEmpty())
    <div class="p-4 text-center text-gray">
        <x-admin.empty-message />
    </div>
@else
    @include('admin.media.partials.table', compact('media', 'ownerLabels', 'sortUrl', 'sortArrow', 'pickerMode'))

    @if ($media->hasPages())
        <div class="d-flex justify-content-center px-3 py-3 border-top border-dark border-opacity-25">
            {{ $media->appends(['mime_filter' => $mimeFilter, 'field' => $field])->links('pagination::bootstrap-5') }}
        </div>
    @endif
@endif
