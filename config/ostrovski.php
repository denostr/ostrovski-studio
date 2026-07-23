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
    | Media variants
    |--------------------------------------------------------------------------
    |
    | name => [max width, WebP quality] for every photo the views reference
    | from public/media (which lives outside git). `php artisan
    | media:optimize` generates the .webp variants from the JPG originals;
    | a PublicPagesTest guard asserts every referenced file has an entry
    | here. The hero renders at up to ~half the viewport (full width on
    | mobile); IMG_5286 fills the wide show card (~1400 CSS px) and is
    | shown upscaled past its full 1440px original width, so it gets the
    | least lossy compression; the rest render at max 460 CSS px
    | (920 retina).
    |
    */

    'media' => [
        'IMG_5283' => [1920, 82],
        'IMG_5234' => [1000, 82],
        'IMG_5282' => [1000, 82],
        'IMG_5286' => [1440, 95],
        'IMG_5287' => [1000, 82],
        'IMG_5285' => [1000, 82],
    ],

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
        'instagram' => env('SOCIAL_INSTAGRAM') ?: 'https://www.instagram.com/ostrovski.official',
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
