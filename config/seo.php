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
];
