@extends('public.layouts.base')

@section('content')
    <section
        class="home-hero bg-img-cover"
        style="background-image: url({{ $page->getFirstMediaUrl('hero-image', 'xl-webp') }});"
    >
        <div class="container home-hero-container">
            <h1>{{ data_get($page->content_data, 'hero.title') }}</h1>

            @if (data_get($page->content_data, 'hero.description'))
                <div class="formatted-text home-hero-description">{!! data_get($page->content_data, 'hero.description') !!}</div>
            @endif
        </div>
    </section>

    @include('public.sections.who-we-are')

    @include('public.sections.services')

    @if ($projects->isNotEmpty())
        <section class="home-portfolio">
            <div class="container home-portfolio-container">
                <div class="home-portfolio-head">
                    <div class="home-portfolio-subtitle">{{ __('base.portfolio') }}</div>
                    <h2 class="home-portfolio-title">{{ __('base.recent_cases') }}</h2>
                    <a
                        href="{{ portfolio_url() }}"
                        class="btn btn-submit btn-view-all home-portfolio-link"
                    >{{ __('base.view_all') }}</a>
                </div>

                <div class="portfolio-projects-scaled-cards">
                    @foreach ($projects as $project)
                        @include('public.pages.portfolio.scaled-card')
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($newsArticles->isNotEmpty())
        <section class="news">
            <div class="container news-container">
                <div class="news-head">
                    <h2 class="news-title">{{ __('base.news_and_events') }}</h2>

                    <x-public.news.categories
                        :categories="$newsCategories"
                        :limit-articles="6"
                    />
                </div>

                <div
                    id="news-list"
                    class="news-list"
                >
                    @include('public.pages.news.list')
                </div>
            </div>
        </section>
    @endif

    @include('public.sections.contact-section')
@endsection
