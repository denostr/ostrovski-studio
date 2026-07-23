{{-- GDPR consent for Google Analytics. Two visual states from the design:
     the bottom bar (compact) and the expanded preferences sheet. One Alpine
     component drives both — see cookieConsent() in resources/js/app.js. --}}
<div x-data="cookieConsent()" x-cloak>
    {{-- Compact bar --}}
    <div class="cookie-bar" x-show="visible && ! expanded" x-transition.opacity
         role="region" aria-label="{{ __('cookie.heading') }}">
        <p class="cookie-text">{{ __('cookie.body') }}</p>
        <div class="cookie-actions">
            <button type="button" class="btn btn-outline btn-small" @click="expanded = true">{{ __('cookie.customize') }}</button>
            <button type="button" class="btn btn-outline btn-small" @click="reject()">{{ __('cookie.reject') }}</button>
            <button type="button" class="btn btn-solid btn-small" @click="acceptAll()">{{ __('cookie.accept') }}</button>
        </div>
    </div>

    {{-- Expanded preferences sheet --}}
    <div class="cookie-sheet-wrap" x-show="visible && expanded" x-transition.opacity>
        {{-- The backdrop only dismisses the sheet — it must NOT persist a
             rejection: a visitor closing the panel hasn't made a choice,
             and an earlier "accept" must survive an accidental click-away. --}}
        <div class="cookie-backdrop" @click="dismiss()"></div>
        <div class="cookie-sheet" role="dialog" aria-modal="true" aria-label="{{ __('cookie.pref_title') }}">
            <h3 class="cookie-sheet-title">{{ __('cookie.pref_title') }}</h3>
            <p class="cookie-sheet-desc">{{ __('cookie.pref_desc') }}</p>

            <div class="cookie-cats">
                <div class="cookie-cat">
                    <div>
                        <div class="cookie-cat-label">{{ __('cookie.necessary') }}</div>
                        <div class="cookie-cat-note">{{ __('cookie.necessary_note') }}</div>
                    </div>
                    <span class="cookie-always">{{ __('cookie.always_on') }}</span>
                </div>
                <button type="button" class="cookie-cat cookie-cat-toggle" @click="analytics = ! analytics"
                        role="switch" :aria-checked="analytics">
                    <span>
                        <span class="cookie-cat-label">{{ __('cookie.analytics') }}</span>
                        <span class="cookie-cat-note">{{ __('cookie.analytics_note') }}</span>
                    </span>
                    <span class="switch" :class="{ on: analytics }" aria-hidden="true"><span class="knob"></span></span>
                </button>
            </div>

            <div class="cookie-sheet-actions">
                <button type="button" class="btn btn-outline" @click="saveChoice()">{{ __('cookie.save') }}</button>
                <button type="button" class="btn btn-solid" @click="acceptAll()">{{ __('cookie.accept') }}</button>
            </div>
        </div>
    </div>
</div>
