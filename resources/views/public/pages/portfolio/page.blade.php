@extends('public.layouts.base')

@section('content')
    <section class="container inner-page-head portfolio-page-head">
        <h1 class="inner-page-title">{{ $page->title }}</h1>

        <x-public.icon.building-outline class="news-page-head-icon" />
    </section>

    <section class="portfolio">
        <div
            id="portfolio"
            class="target-anchor-section"
        ></div>

        <div class="container portfolio-container">
            <div class="portfolio-projects-scaled-cards">
                @foreach ($projects as $project)
                    @include('public.pages.portfolio.scaled-card')
                @endforeach
            </div>

            @php
                $paginationView = view()->exists('vendor.pagination.public')
                    ? 'vendor.pagination.public'
                    : 'pagination::default';
            @endphp
            <nav class="pagination news-pagination">{{ $projects->withQueryString()->links($paginationView) }}
            </nav>
        </div>
    </section>
@endsection
