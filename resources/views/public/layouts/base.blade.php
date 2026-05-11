<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    @php
        $page_seo_title = isset($page->seo_title) && filled($page->seo_title) ? $page->seo_title : config('app.name');
        $page_seo_description = isset($page->seo_description) && filled($page->seo_description) ? $page->seo_description : null;
        $page_seo_keywords = isset($page->seo_keywords) && filled($page->seo_keywords) ? $page->seo_keywords : null;
    @endphp

    <title>{{ $page_seo_title }}</title>

    @if($page_seo_description)
        <meta content="{{ $page_seo_description }}" name="description">
    @endif
    @if($page_seo_keywords)
        <meta content="{{ $page_seo_keywords }}" name="keywords">
    @endif

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="{{ url()->current() }}" rel="canonical">

    @php
        $publishedLocales = published_languages_keys();
        $isDraftLocale = !in_array(current_locale(), $publishedLocales, true);
        $fallbackLocale = config('app.fallback_locale', 'en');
    @endphp

    @if($isDraftLocale)
        {{-- Draft locales are reachable but should not be indexed until published. --}}
        <meta name="robots" content="noindex,follow">
    @else
        @foreach($publishedLocales as $lang)
            <link rel="alternate" hreflang="{{ $lang }}" href="{{ localized_url($lang) }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ localized_url($fallbackLocale) }}">
    @endif

    <meta property="og:locale" content="{{ app()->getLocale() }}">
    <meta property="og:title" content="{{ $page_seo_title }}">
    @if($page_seo_description)
        <meta property="og:description" content="{{ $page_seo_description }}">
    @endif
    <meta property="og:image" content="{{ config('app.url') }}/img/og-logo.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="website">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $page_seo_title }}">
    @if($page_seo_description)
        <meta name="twitter:description" content="{{ $page_seo_description }}">
    @endif
    <meta name="twitter:image" content="{{ config('app.url') }}/img/og-logo.png">

    <x-public.structured-data :page="$page ?? null" />

    @include('public.fragments.favicon')

    <meta name="theme-color" content="#122F4D">

    @include('public.fragments.fonts')

    @vite('resources/css/public/public.scss')
</head>

@php
    $bodyClass = 'home-page';

    if (Route::is('public.portfolio.project')) {
        $bodyClass = 'portfolio-project-page';
    } elseif (Route::is('public.news.article')) {
        $bodyClass = 'news-article-page';
    } elseif (Route::is(['public.news.*', 'public.portfolio.*'])) {
        $bodyClass = 'post-page';
    } elseif (!Route::is('public.home')) {
        $bodyClass = 'inner-page';
    }
@endphp

<body
    {{ isset($page) ? 'id=' . $page->slug . '-page' : null }}
    class="{{ $bodyClass }}"
>

@include('public.sections.header')

<main>
    @yield('content')
</main>

@include('public.sections.footer')

@vite('resources/js/public/public.js')

<script>
    (function () {
        if (window.__tndHeaderFallbackInited) return;
        window.__tndHeaderFallbackInited = true;

        var root = document.documentElement;
        var toggleClassname = 'is-opened-main-menu';

        function applyScrollState() {
            root.classList.toggle('is-page-scrolled', window.scrollY > 0);
        }

        function bindMenuToggle() {
            var toggleButton = document.getElementById('main-menu-toggle-button');
            if (!toggleButton || toggleButton.dataset.headerFallbackBound === '1' || toggleButton.dataset.headerMenuBound === '1') return;

            toggleButton.dataset.headerFallbackBound = '1';

            toggleButton.addEventListener('click', function () {
                root.classList.toggle(toggleClassname);
            });

            document.addEventListener('click', function (event) {
                var targetMenu = event && event.target && event.target.closest ? event.target.closest('#main-menu') : null;
                var targetToggleButton = event && event.target && event.target.closest ? event.target.closest('#main-menu-toggle-button') : null;

                if (targetMenu || targetToggleButton) return;
                root.classList.remove(toggleClassname);
            });

            window.addEventListener('scroll', function () {
                root.classList.remove(toggleClassname);
            }, { passive: true });
        }

        function initHeaderFallback() {
            applyScrollState();
            bindMenuToggle();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initHeaderFallback, { once: true });
        } else {
            initHeaderFallback();
        }

        window.addEventListener('scroll', applyScrollState, { passive: true });
    })();
</script>

<div class="modals">
    @include('public.modals.contact-modal')
    @include('public.modals.success-send-modal')
    @include('public.modals.error-send-modal')
</div>

@stack('footer-scripts')

</body>
</html>
