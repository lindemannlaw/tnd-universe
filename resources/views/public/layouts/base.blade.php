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

    @php
        $hasGeoCoords = isset($page)
            && filled($page->lat ?? null)
            && filled($page->lon ?? null);
        $geoRegion = $page->geo_region ?? null;
        $geoPlacenameRaw = isset($page->location) && is_string($page->location)
            ? $page->location
            : null;
        $geoPlacename = $geoPlacenameRaw
            ? trim(rtrim(explode(',', $geoPlacenameRaw, 2)[0], " \t\n\r\0\x0B."))
            : null;
        $geoLat = $hasGeoCoords ? round((float) $page->lat, 7) : null;
        $geoLon = $hasGeoCoords ? round((float) $page->lon, 7) : null;
    @endphp

    @if($hasGeoCoords)
        <meta name="geo.position" content="{{ $geoLat }};{{ $geoLon }}">
        <meta name="ICBM" content="{{ $geoLat }}, {{ $geoLon }}">
    @endif
    @if(filled($geoRegion))
        <meta name="geo.region" content="{{ $geoRegion }}">
    @endif
    @if(filled($geoPlacename))
        <meta name="geo.placename" content="{{ $geoPlacename }}">
    @endif

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php $seoVerification = config('seo.verification', []); @endphp
    @if(filled($seoVerification['google'] ?? null))
        <meta name="google-site-verification" content="{{ $seoVerification['google'] }}">
    @endif
    @if(filled($seoVerification['bing'] ?? null))
        <meta name="msvalidate.01" content="{{ $seoVerification['bing'] }}">
    @endif
    @if(filled($seoVerification['yandex'] ?? null))
        <meta name="yandex-verification" content="{{ $seoVerification['yandex'] }}">
    @endif
    @if(filled($seoVerification['pinterest'] ?? null))
        <meta name="p:domain_verify" content="{{ $seoVerification['pinterest'] }}">
    @endif

    @php $gtagIds = config('seo.google_tag_ids', []); @endphp
    @if(! empty($gtagIds))
        {{-- Google tag (gtag.js) — Ads / GA4 --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gtagIds[0] }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            @foreach($gtagIds as $gid)
                gtag('config', @json($gid));
            @endforeach
        </script>
    @endif

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
        @php $hreflangAliases = config('seo.hreflang_aliases', []); @endphp
        @foreach($publishedLocales as $lang)
            <link rel="alternate" hreflang="{{ $lang }}" href="{{ localized_url($lang) }}">
            @foreach(($hreflangAliases[$lang] ?? []) as $aliasTag)
                <link rel="alternate" hreflang="{{ $aliasTag }}" href="{{ localized_url($lang) }}">
            @endforeach
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ localized_url($fallbackLocale) }}">
    @endif

    @php
        // og:locale needs xx_XX format (e.g. en_US, de_DE). Pull from
        // laravellocalization's "regional" field; fall back to bare code.
        $ogLocaleOf = function ($code) {
            $entry = config("laravellocalization.supportedLocales.$code");
            return $entry['regional'] ?? $code;
        };

        // Per-page hero image as social card. Falls back to site logo when
        // the page has no hero or doesn't expose Spatie Media at all.
        $ogImageUrl = config('app.url') . '/img/og-logo.png';
        if (isset($page) && is_object($page) && method_exists($page, 'getFirstMediaUrl')) {
            $heroCollection = $page->mediaHero ?? 'hero';
            try {
                $candidate = $page->getFirstMediaUrl($heroCollection, 'lg-webp')
                    ?: $page->getFirstMediaUrl($heroCollection);
                if (filled($candidate)) {
                    $ogImageUrl = $candidate;
                }
            } catch (\Throwable $e) {
                // keep logo fallback
            }
        }

        // og:type — "article" for individual content detail pages, else "website".
        $ogType = 'website';
        if (isset($page) && is_object($page) && (
            $page instanceof \App\Models\Project
            || $page instanceof \App\Models\NewsArticle
            || $page instanceof \App\Models\Service
        )) {
            $ogType = 'article';
        }
    @endphp

    <meta property="og:locale" content="{{ $ogLocaleOf(app()->getLocale()) }}">
    @foreach($publishedLocales as $lang)
        @if($lang !== app()->getLocale())
            <meta property="og:locale:alternate" content="{{ $ogLocaleOf($lang) }}">
        @endif
    @endforeach
    <meta property="og:title" content="{{ $page_seo_title }}">
    @if($page_seo_description)
        <meta property="og:description" content="{{ $page_seo_description }}">
    @endif
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="{{ $ogType }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $page_seo_title }}">
    @if($page_seo_description)
        <meta name="twitter:description" content="{{ $page_seo_description }}">
    @endif
    <meta name="twitter:image" content="{{ $ogImageUrl }}">

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
