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
            </div>
        </div>

        <x-public.icon.building-outline class="news-article-hero-icon" />
    </section>

    <section class="news-article-content">
        <div class="container news-article-content-container">
            <article class="formatted-text news-article-description">
                <div class="news-article-meta">
                    <p class="news-article-meta-item">
                        <span>{{ __('base.date') }}</span>
                        <br>
                        <span class="h2"><strong>{{ $article->created_at->translatedFormat('d M Y') }}</strong></span>
                    </p>
                    @if ($article->first_category)
                        <p class="news-article-meta-item">
                            <span>{{ __('base.category') }}</span>
                            <br>
                            <span class="h2"><strong>{{ $article->first_category->name }}</strong></span>
                        </p>
                    @endif

                    @if ($article->link_top_active)
                        @php
                            $topText = $article->getTranslation('link_top_text', app()->getLocale(), false)
                                    ?: $article->getTranslation('link_top_text', config('app.fallback_locale'), false);
                            $topFile = $article->linkTopMedia;
                            $topHref = $topFile ? $topFile->getUrl() : $article->link_top_url;
                            $topImage = $article->link_top_show_image ? $article->linkTopImage : null;
                        @endphp
                        @if ($topText && $topHref)
                            <a href="{{ $topHref }}" class="arrow-link news-article-link-top">
                                {{ $topText }}
                                <svg width="32" height="10" viewBox="0 0 32 10" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M0 5H30M30 5L26 1M30 5L26 9" stroke="currentColor" stroke-width="1.2"/>
                                </svg>
                            </a>
                            @if ($topImage)
                                <a href="{{ $topHref }}" class="news-article-link-image news-article-link-top-image"
                                   @if($topFile) download="{{ $topFile->file_name }}" @endif>
                                    <img src="{{ $topImage->getUrl() }}" alt="{{ $topText }}" loading="lazy">
                                </a>
                            @endif
                        @endif
                    @endif
                </div>
                <div class="news-article-content-body">
                    {!! $article->description !!}
                </div>
            </article>
        </div>

        @if ($article->link_bottom_active)
            @php
                $bottomText = $article->getTranslation('link_bottom_text', app()->getLocale(), false)
                            ?: $article->getTranslation('link_bottom_text', config('app.fallback_locale'), false);
                $bottomFile = $article->linkBottomMedia;
                $bottomHref = $bottomFile ? $bottomFile->getUrl() : $article->link_bottom_url;
                $bottomIsDownload = (bool) $bottomFile;
                $bottomFileSize = $bottomFile ? number_format($bottomFile->size / (1024 * 1024), 2) : null;
                $bottomImage = $article->link_bottom_show_image ? $article->linkBottomImage : null;
            @endphp
            @if ($bottomText && $bottomHref)
                <div class="container">
                    <div class="news-article-files file-download-list">
                        <a
                            href="{{ $bottomHref }}"
                            class="file-download"
                            @if($bottomIsDownload) download="{{ $bottomFile->file_name }}" @endif
                        >
                            <span class="file-download-name">{{ $bottomText }}</span>
                            @if ($bottomFileSize)
                                <span class="file-download-size">{{ $bottomFileSize }} Mb</span>
                            @endif
                        </a>
                        @if ($bottomImage)
                            <a href="{{ $bottomHref }}" class="news-article-link-image news-article-link-bottom-image"
                               @if($bottomIsDownload) download="{{ $bottomFile->file_name }}" @endif>
                                <img src="{{ $bottomImage->getUrl() }}" alt="{{ $bottomText }}" loading="lazy">
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        @endif

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
