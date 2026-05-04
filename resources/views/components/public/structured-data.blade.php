@props(['page' => null])

@php
    $siteName = config('app.name');
    $siteUrl = rtrim(config('app.url'), '/');
    $logoUrl = $siteUrl . '/img/og-logo.png';
    $homeUrl = route('public.home');
    $currentUrl = url()->current();
    $locale = config('app.locale');

    $pageTitle = isset($page->seo_title) && filled($page->seo_title)
        ? (string) $page->seo_title
        : $siteName;
    $pageDescription = isset($page->seo_description) && filled($page->seo_description)
        ? (string) $page->seo_description
        : null;

    $isHome = $currentUrl === $homeUrl || $currentUrl === $homeUrl . '/';

    $graph = [];

    $graph[] = array_filter([
        '@type' => 'Organization',
        '@id' => $siteUrl . '/#organization',
        'name' => $siteName,
        'url' => $siteUrl,
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $logoUrl,
            'width' => 1200,
            'height' => 630,
        ],
    ]);

    $graph[] = [
        '@type' => 'WebSite',
        '@id' => $siteUrl . '/#website',
        'url' => $siteUrl,
        'name' => $siteName,
        'inLanguage' => $locale,
        'publisher' => ['@id' => $siteUrl . '/#organization'],
    ];

    $graph[] = array_filter([
        '@type' => 'WebPage',
        '@id' => $currentUrl . '#webpage',
        'url' => $currentUrl,
        'name' => $pageTitle,
        'description' => $pageDescription,
        'inLanguage' => $locale,
        'isPartOf' => ['@id' => $siteUrl . '/#website'],
        'about' => ['@id' => $siteUrl . '/#organization'],
    ]);

    if (! $isHome && isset($page)) {
        $crumbs = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => $siteName,
                'item' => $homeUrl,
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $pageTitle,
                'item' => $currentUrl,
            ],
        ];

        $graph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $currentUrl . '#breadcrumb',
            'itemListElement' => $crumbs,
        ];
    }

    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ];
@endphp

<script type="application/ld+json">
{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
