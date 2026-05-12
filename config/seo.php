<?php

return [
    /*
     * Regional hreflang aliases. The site is published in a small set of base
     * locales (e.g. "en", "de"), but Google benefits from regional variants
     * pointing at the same URL — useful for ad/geo-targeting USA, UK, DACH.
     * Each entry maps a base locale to additional hreflang tags rendered for
     * the same localized URL.
     */
    'hreflang_aliases' => [
        'en' => ['en-US', 'en-GB'],
        'de' => ['de-DE', 'de-CH', 'de-AT'],
    ],

    /*
     * Site-verification meta tags. Paste the content="…" value from each
     * provider (Google Search Console, Bing Webmaster Tools, Yandex, Pinterest)
     * here — they'll be rendered into the <head> on every page.
     *
     * GOOGLE_SITE_VERIFICATION can be set in .env to avoid editing config.
     */
    'verification' => [
        'google' => env('GOOGLE_SITE_VERIFICATION'),
        'bing' => env('BING_SITE_VERIFICATION'),
        'yandex' => env('YANDEX_VERIFICATION'),
        'pinterest' => env('PINTEREST_VERIFICATION'),
    ],

    /*
     * Google tag IDs (gtag.js). Comma-separated list so a single page can
     * carry both a GA4 measurement ID (G-XXXXXXX) and one or more Google
     * Ads tags (AW-XXXXXXXXX). Example .env entry:
     *
     *   GOOGLE_TAG_IDS=AW-314748583,G-ABCDEF1234
     *
     * Privacy note: this script transfers visitor data to Google. For
     * EU/CH visitors you must obtain consent before loading it — wire a
     * consent banner upstream if you don't already have one.
     */
    'google_tag_ids' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('GOOGLE_TAG_IDS', ''))
    ))),
];
