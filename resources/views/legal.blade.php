@php
    // $page is one of config('ostrovski.legal') — validated by the route.
    $base = 'legal.'.$page;

    // Guard with Lang::has() so a forgotten translation key never leaks as a
    // visible "legal.foo.intro" placeholder. An explicit '' silences the block.
    $intro = Lang::has($base.'.intro') ? trim(__($base.'.intro')) : '';
@endphp

<x-layout :title="__($base.'.title').' — OSTROVSKI'">
    <article class="legal">
        <div class="legal-inner">
            <p class="legal-eyebrow">{{ __('legal.eyebrow') }}</p>
            <h1 class="legal-title">{{ __($base.'.title') }}</h1>
            @if ($intro !== '')
                <p class="legal-intro">{{ $intro }}</p>
            @endif

            @foreach (__($base.'.sections') as $section)
                <section class="legal-section">
                    @if (! empty($section['heading']))
                        <h2>{{ $section['heading'] }}</h2>
                    @endif
                    @foreach ($section['body'] as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </section>
            @endforeach

            <p class="legal-updated">{{ __('legal.last_updated') }}</p>
        </div>
    </article>
</x-layout>
