@extends('public.layouts.base')

@section('content')
    <section class="news-article-hero">
        <div class="container news-article-hero-container">
            <nav class="breadcrumbs is-breadcrumbs-bg-dark">
                <ul>
                    <li><a href="{{ route('public.news') }}">{{ __('base.news') }}</a></li>
                    <li>{{ $article->title }}</li>
                </ul>
            </nav>

            <div class="news-article-hero-body">
                <h1 class="news-article-title">{{ $article->title }}</h1>

                <div class="news-article-hero-info">
                    <div class="news-article-hero-info-body">
                        <time
                            datetime="{{ $article->created_at->format('Y-m-d') }}">{{ $article->created_at->translatedFormat('d M Y') }}</time>
                        <div>{{ $article->first_category->name }}</div>
                    </div>
                </div>
            </div>
        </div>

        <x-public.icon.building-outline class="news-article-hero-icon" />
    </section>

    <section class="news-article-content">
        <div class="container news-article-content-container">
            <article class="formatted-text news-article-description">
                {!! $article->description !!}
            </article>
        </div>

        @if ($relatedArticles->isNotEmpty())
            <div class="container related-news-articles">
                <div class="related-news-articles-head">
                    <h3 class="related-news-articles-title">{{ __('base.related_articles') }}</h3>
                    <a
                        href="{{ route('public.news') }}"
                        class="btn btn-submit btn-view-all related-news-articles-link"
                    >{{ __('base.all') }}</a>
                </div>

                <div class="news-list related-news-articles-list">
                    @foreach ($relatedArticles as $article)
                        <x-public.news.article-card :article="$article" />
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endsection
