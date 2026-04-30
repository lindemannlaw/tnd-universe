@php
    /**
     * Owner label map: short class name → translated label.
     * Falls back to class_basename for unknown types.
     */
    $ownerLabels = [
        'Project'     => __('admin.project'),
        'NewsArticle' => __('admin.articles'),
        'Service'     => __('admin.service'),
        'ServiceCategory' => __('admin.category'),
        'Page'        => __('admin.page'),
        'SiteSection' => __('admin.sections'),
        'Leader'      => __('admin.leader'),
        'User'        => __('admin.profile'),
    ];

    $sortOptions = [
        'name'       => __('admin.name'),
        'size'       => __('admin.size'),
        'updated_at' => __('admin.last_modified'),
    ];

    /**
     * Build URL for a sort header — toggles direction if already active.
     */
    $sortUrl = function (string $key) use ($sortBy, $sortDir) {
        $nextDir = ($sortBy === $key && $sortDir === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort_by' => $key, 'sort_dir' => $nextDir]);
    };

    $sortArrow = function (string $key) use ($sortBy, $sortDir) {
        if ($sortBy !== $key) return null;
        return $sortDir === 'asc' ? 'arrow-up' : 'arrow-down';
    };
@endphp

@php $pickerMode = $pickerMode ?? false; @endphp

@if ($media->isEmpty())
    <div class="d-flex flex-auto">
        <x-admin.empty-message />
    </div>
@else
    @if ($view === 'grid')
        @include('admin.media.partials.grid', compact('media', 'ownerLabels', 'sortOptions', 'sortBy', 'sortDir', 'pickerMode'))
    @else
        @include('admin.media.partials.table', compact('media', 'ownerLabels', 'sortUrl', 'sortArrow', 'pickerMode'))
    @endif

    @if ($media->hasPages())
        <div class="d-flex justify-content-center px-3 px-sm-4 py-3 border-top border-dark border-opacity-25">
            {{ $media->links('pagination::bootstrap-5') }}
        </div>
    @endif
@endif
