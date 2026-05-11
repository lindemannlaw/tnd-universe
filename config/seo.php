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
];
