<x-layout>
    {{-- HERO --}}
    <section class="hero">
        <div class="hero-copy">
            <div class="hero-kicker">{{ __('home.hero.kicker') }}</div>
            <h1 class="hero-name">Katya<br><span>Ostrovski</span></h1>
            <p class="hero-roles">{{ __('home.hero.roles') }}</p>
            {{-- Deliberately the same string as the meta/OG description —
                 one key so the two can't drift apart. --}}
            <p class="hero-lead">{{ __('common.tagline') }}</p>
        </div>
        <div class="hero-media">
            <div class="hero-img" style="background-image:url('{{ asset('media/IMG_5283.webp') }}')"></div>
            <div class="hero-shade"></div>
        </div>
    </section>

    {{-- ABOUT longread --}}
    <section id="about" class="about">
        <div class="section-head">
            <span class="section-no">(02)</span>
            <span class="section-label">{{ __('home.about.label') }}</span>
        </div>

        <div class="about-headline-wrap">
            <p class="about-headline">{{ __('home.about.headline') }}</p>
        </div>

        <div class="about-row">
            <div class="about-text">
                <p class="lead-par">{{ __('home.about.p1') }}</p>
                <p>{{ __('home.about.p2') }}</p>
            </div>
            <div class="about-media">
                <div class="about-img" style="background-image:url('{{ asset('media/IMG_5234.webp') }}')"></div>
            </div>
        </div>

        <div class="about-row about-row-flip">
            <div class="about-media">
                <div class="about-img" style="background-image:url('{{ asset('media/IMG_5287.webp') }}')"></div>
            </div>
            <div class="about-text">
                <p>{{ __('home.about.p3') }}</p>
                <p>{{ __('home.about.p4') }}</p>
                <p>{{ __('home.about.p5') }}</p>
            </div>
        </div>

        <div class="about-row">
            <div class="about-text">
                <p class="lead-par">{{ __('home.about.p6') }}</p>
                <p>{{ __('home.about.p7') }}</p>
            </div>
            <div class="about-media">
                <div class="about-img" style="background-image:url('{{ asset('media/IMG_5286.webp') }}');background-position:50% 75%;"></div>
            </div>
        </div>

        <div class="about-closing">
            <p>{{ __('home.about.closing') }}</p>
        </div>
    </section>

    {{-- SERVICES — cards layout A (image-forward) --}}
    <section id="services" class="services">
        <div class="section-head">
            <span class="section-no">(03)</span>
            <span class="section-label">{{ __('services.label') }}</span>
        </div>
        <h2 class="services-headline">{{ __('services.headline') }}</h2>

        <div class="services-groups">
            <div id="services-dj" class="services-group">
                <div class="cat-head">
                    <span class="cat-no">/ 01</span>
                    <span class="cat-label">{{ __('services.cat_dj') }}</span>
                </div>
                <div class="cards-pair">
                    <x-service-card service="dj" image="media/IMG_5282.webp" position="50% 50%"/>
                    <x-service-card service="dj_lessons" image="media/IMG_5285.webp" position="50% 12%"/>
                </div>
            </div>

            <div id="services-show" class="services-group">
                <div class="cat-head">
                    <span class="cat-no">/ 02</span>
                    <span class="cat-label">{{ __('services.cat_show') }}</span>
                </div>
                <x-service-card service="choreo" image="media/IMG_5283.webp" position="50% 22%" wide/>
            </div>
        </div>
    </section>
</x-layout>
