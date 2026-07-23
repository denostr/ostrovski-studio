@php
    // The switcher must land on the *current* page in the other locale —
    // same inputs as the layout's hreflang block.
    ['name' => $langBaseName, 'params' => $langParams] = current_route_base();
@endphp
<div class="lang-switch" role="group" aria-label="{{ __('common.nav_language') }}">
    @foreach (config('ostrovski.locales') as $loc)
        @unless ($loop->first)<span class="sep" aria-hidden="true">/</span>@endunless
        {{-- `?_lang=` signals an explicit user choice — the SetLocale
             middleware persists it in the year-long `locale` cookie
             and 302s to a clean URL, so the GeoIP auto-redirect
             won't bounce the visitor back to /de on the next visit. --}}
        <a href="{{ loc_route($langBaseName, $langParams, $loc) }}?_lang={{ $loc }}"
           @class(['active' => app()->getLocale() === $loc])
           @if (app()->getLocale() === $loc) aria-current="true" @endif>{{ strtoupper($loc) }}</a>
    @endforeach
</div>
