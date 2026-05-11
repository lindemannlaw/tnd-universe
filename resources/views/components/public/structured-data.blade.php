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

    $pageDateModified = isset($page) && is_object($page) && isset($page->updated_at)
        ? optional($page->updated_at)->toAtomString()
        : null;

    $graph[] = array_filter([
        '@type' => 'WebPage',
        '@id' => $currentUrl . '#webpage',
        'url' => $currentUrl,
        'name' => $pageTitle,
        'description' => $pageDescription,
        'inLanguage' => $locale,
        'isPartOf' => ['@id' => $siteUrl . '/#website'],
        'about' => ['@id' => $siteUrl . '/#organization'],
        'dateModified' => $pageDateModified,
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

    if (
        isset($page)
        && $page instanceof \App\Models\Project
        && filled($page->lat)
        && filled($page->lon)
    ) {
        // Collect images: hero first, then gallery. lg-webp preferred,
        // raw URL as fallback. Deduplicated, hero stays first.
        $images = [];
        try {
            $hero = $page->getFirstMediaUrl($page->mediaHero, 'lg-webp')
                ?: $page->getFirstMediaUrl($page->mediaHero);
            if (filled($hero)) $images[] = $hero;
        } catch (\Throwable $e) {}

        try {
            foreach ($page->getMedia($page->mediaGallery ?? 'gallery') as $media) {
                $url = $media->getFullUrl('lg-webp') ?: $media->getFullUrl();
                if (filled($url)) $images[] = $url;
            }
        } catch (\Throwable $e) {}

        $images = array_values(array_unique($images));
        $heroImageUrl = $images[0] ?? null;

        $rawLocality = is_string($page->location) ? $page->location : null;
        $addressLocality = $rawLocality
            ? trim(rtrim(explode(',', $rawLocality, 2)[0], " \t\n\r\0\x0B."))
            : null;

        // floorSize: parse digits from $page->area (e.g. "470 m²" → 470).
        $floorSize = null;
        if (filled($page->area ?? null) && preg_match('/(\d+(?:[.,]\d+)?)/', (string) $page->area, $mm)) {
            $floorSize = [
                '@type' => 'QuantitativeValue',
                'value' => (float) str_replace(',', '.', $mm[1]),
                'unitCode' => 'MTK', // square meters (UN/CEFACT)
                'unitText' => 'm²',
            ];
        }

        // numberOfRooms: peek into translatable property_details for a "bedrooms" key,
        // falling back to "rooms" if present. Empty if neither is set.
        $numberOfRooms = null;
        $details = is_array($page->property_details ?? null) ? $page->property_details : [];
        foreach (['bedrooms', 'rooms', 'number_of_rooms'] as $k) {
            if (! empty($details[$k]) && preg_match('/(\d+)/', (string) $details[$k], $mm)) {
                $numberOfRooms = (int) $mm[1];
                break;
            }
        }

        $residence = array_filter([
            '@type' => 'SingleFamilyResidence',
            '@id' => $currentUrl . '#residence',
            'name' => (string) $page->title,
            'description' => $pageDescription,
            'url' => $currentUrl,
            'image' => count($images) > 1 ? $images : ($heroImageUrl ?: null),
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'addressLocality' => $addressLocality,
                'addressCountry' => $page->geo_region,
            ]),
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => rtrim(rtrim(sprintf('%.7F', (float) $page->lat), '0'), '.'),
                'longitude' => rtrim(rtrim(sprintf('%.7F', (float) $page->lon), '0'), '.'),
            ],
            'floorSize' => $floorSize,
            'numberOfRooms' => $numberOfRooms,
        ]);

        $graph[] = $residence;

        $graph[] = array_filter([
            '@type' => 'RealEstateListing',
            '@id' => $currentUrl . '#listing',
            'url' => $currentUrl,
            'name' => (string) $page->title,
            'description' => $pageDescription,
            'image' => count($images) > 1 ? $images : ($heroImageUrl ?: null),
            'about' => ['@id' => $currentUrl . '#residence'],
            'datePosted' => optional($page->created_at)->toAtomString(),
            'dateModified' => $pageDateModified,
            'offers' => [
                '@type' => 'Offer',
                'availability' => 'https://schema.org/InStock',
                'url' => $currentUrl,
            ],
        ]);
    }

    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ];
@endphp

<script type="application/ld+json">
{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
