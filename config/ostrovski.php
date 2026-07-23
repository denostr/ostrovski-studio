<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site locales
    |--------------------------------------------------------------------------
    |
    | Languages the public site is available in. The first entry is the
    | default (English at the root; German under the /de prefix). The legal
    | pages are also served in both of these locales.
    |
    */

    'locales' => ['en', 'de'],

    /*
    | Open Graph language_TERRITORY codes per locale — consumed by the
    | layout's og:locale tags. Extend together with `locales`.
    */

    'og_locales' => ['en' => 'en_US', 'de' => 'de_DE'],

    /*
    |--------------------------------------------------------------------------
    | Services
    |--------------------------------------------------------------------------
    |
    | Keys of the bookable services. Each drives an enquiry-modal variant and
    | a card in the services section; labels and copy live in the `services`
    | translation file. The Heels On site links here via its
    | OSTROVSKI_SERVICES_URL env — route name `services`.
    |
    */

    'services' => ['dj', 'dj_lessons', 'choreo'],

    /*
    |--------------------------------------------------------------------------
    | Legal pages
    |--------------------------------------------------------------------------
    |
    | Slugs of the legally required pages, served at the root and under /de
    | (e.g. /impressum, /de/datenschutz). The slugs stay identical across
    | locales — they are established German legal terms. Their content lives
    | in the `legal` translation file.
    |
    */

    'legal' => ['impressum', 'agb', 'datenschutz'],

    /*
    |--------------------------------------------------------------------------
    | Contact & external links
    |--------------------------------------------------------------------------
    |
    | `email` receives the enquiry-form notifications. The Instagram URL is
    | a placeholder until the real profile link is provided.
    |
    */

    'email' => env('OSTROVSKI_EMAIL', 'info@ostrovski.studio'),

    'social' => [
        'instagram' => env('SOCIAL_INSTAGRAM') ?: 'https://instagram.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & social sharing
    |--------------------------------------------------------------------------
    |
    | Google Analytics is loaded only after the visitor grants analytics
    | consent in the cookie banner — see resources/js/app.js. Leave the ID
    | empty to disable analytics entirely. `og_image` is the absolute or
    | app-relative path to the Open Graph preview image.
    |
    */

    'analytics' => [
        'ga_id' => env('GOOGLE_ANALYTICS_ID'),
    ],

    'og_image' => env('OSTROVSKI_OG_IMAGE'),

];
