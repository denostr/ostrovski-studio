@props(['title' => null, 'description' => null])
@php
    // hreflang must point at the *current* page in each locale, not just the
    // home page — so the alternates resolve correctly on the legal routes too.
    ['name' => $hreflangBaseName, 'params' => $hreflangParams] = current_route_base();

    $pageTitle = $title ?? 'OSTROVSKI — Katya Ostrovski';
    $pageDescription = $description ?? __('common.tagline');
    $canonical = url()->current();

    // Open Graph expects language_TERRITORY locale codes.
    $ogLocales = config('ostrovski.og_locales');

    $ogImagePath = config('ostrovski.og_image');
    $ogImage = $ogImagePath
        ? (str_starts_with($ogImagePath, 'http') ? $ogImagePath : asset($ogImagePath))
        : null;

    // schema.org structured data — empty values are dropped so placeholders
    // never leak into the markup. Email intentionally omitted — JSON-LD is
    // not run through Cloudflare's email obfuscation (search engines need
    // to parse it as-is), so it would expose the address to scrapers.
    $structuredData = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => 'Katya Ostrovski',
        'jobTitle' => __('home.hero.roles'),
        'description' => $pageDescription,
        'url' => loc_route('home'),
        'image' => $ogImage,
        'sameAs' => array_values(array_filter(
            config('ostrovski.social'),
            fn (?string $url): bool => filled($url) && $url !== '#',
        )),
    ]);
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $canonical }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <meta name="theme-color" content="#16171a">

    @foreach (config('ostrovski.locales') as $alt)
        <link rel="alternate" hreflang="{{ $alt }}" href="{{ loc_route($hreflangBaseName, $hreflangParams, $alt) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ loc_route($hreflangBaseName, $hreflangParams, config('ostrovski.locales')[0]) }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="OSTROVSKI">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:locale" content="{{ $ogLocales[app()->getLocale()] ?? 'en_US' }}">
    @foreach (config('ostrovski.locales') as $alt)
        @if ($alt !== app()->getLocale())
            <meta property="og:locale:alternate" content="{{ $ogLocales[$alt] ?? $alt }}">
        @endif
    @endforeach
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    @if ($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    <script type="application/ld+json">@json($structuredData)</script>

    @if ($gaId = config('ostrovski.analytics.ga_id'))
        {{-- Exposed for resources/js/app.js — Google Analytics loads only
             after the visitor grants analytics consent. --}}
        <script>window.ostrovskiGaId = @js($gaId);</script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if (config('services.turnstile.site_key'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
</head>
<body>
    <a class="skip-link" href="#main">{{ __('common.skip_to_content') }}</a>

    {{-- x-data on the page wrapper makes it an Alpine root — without it,
         Alpine never initialises @click directives on the service cards and
         the footer's cookie-settings button (they'd be dead markup). --}}
    <div x-data>
        <x-topbar/>

        <main id="main">
            {{ $slot }}
        </main>

        <x-site-footer/>

        <x-enquiry-modal/>
    </div>

    {{-- Cookie banner is shown only when Google Analytics is configured.
         Without analytics there are no non-essential cookies, so consent is
         not required (necessary cookies are exempt under GDPR / § 25 TTDSG). --}}
    @if (config('ostrovski.analytics.ga_id'))
        <x-cookie-banner/>
    @endif
</body>
</html>
