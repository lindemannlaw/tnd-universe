@php
    $paginationView = view()->exists('vendor.pagination.public')
        ? 'vendor.pagination.public'
        : 'pagination::default';
@endphp
{{ $newsArticles->withQueryString()->links($paginationView) }}
