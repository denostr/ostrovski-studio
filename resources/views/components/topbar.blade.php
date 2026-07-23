<header class="topbar" :class="{ 'menu-open': open }">
    <a class="brand" href="{{ loc_route('home') }}">OSTROVSKI</a>

    <div class="topbar-right">
        <nav class="topnav" aria-label="{{ __('common.nav_services') }}">
            <a href="{{ loc_route('home') }}#services-dj">{{ __('services.cat_dj') }}</a>
            <span class="dot" aria-hidden="true">·</span>
            <a href="{{ loc_route('home') }}#services-show">{{ __('services.cat_show') }}</a>
        </nav>

        <span class="topbar-divider" aria-hidden="true"></span>

        <x-lang-switch/>

        <button type="button" class="burger" x-ref="burger"
                :class="{ open }"
                :aria-expanded="open"
                @click="toggle()"
                aria-label="{{ __('common.menu') }}">
            <span></span><span></span><span></span>
        </button>
    </div>

    {{-- Mobile overlay: the same service links + language switcher that live
         in the bar on desktop. Teleported to <body> so its z-index is not
         trapped inside the fixed topbar's stacking context (otherwise the
         cookie banner would paint over it). Closes on link tap, Escape, or a
         resize back to desktop width. --}}
    <template x-teleport="body">
        <div class="menu-overlay" x-show="open" x-cloak x-transition.opacity
             @keydown.escape.window="close()"
             @resize.window.debounce.150ms="window.innerWidth > 760 && close()">
            <nav class="menu-nav" aria-label="{{ __('common.nav_services') }}">
                <a href="{{ loc_route('home') }}#services-dj" @click="close()">{{ __('services.cat_dj') }}</a>
                <a href="{{ loc_route('home') }}#services-show" @click="close()">{{ __('services.cat_show') }}</a>
            </nav>
            <x-lang-switch/>
        </div>
    </template>
</header>
