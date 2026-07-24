{{-- The booking-enquiry modal. A single component serves all services —
     triggers open it via a window `enquiry-open` event carrying { service }.
     The form posts JSON to the `enquiry` route; success is shown inline.
     See enquiry() in resources/js/app.js. --}}
<div class="modal-wrap"
     x-data="enquiry({
        url: @js(loc_route('enquiry')),
        titles: @js(collect(config('ostrovski.services'))->mapWithKeys(fn ($s) => [$s => __('services.'.$s.'.title')])->all()),
        errorText: @js(__('enquiry.errors.generic')),
        expiredText: @js(__('enquiry.errors.expired')),
     })"
     x-cloak
     x-show="visible"
     x-effect="$el.inert = ! visible"
     @enquiry-open.window="openModal($event.detail)"
     @keydown.escape.window="closeModal()">
    <div class="modal-backdrop" @click="closeModal()"></div>

    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-head">
            <div>
                <div class="modal-kicker">{{ __('enquiry.kicker') }}</div>
                <h3 class="modal-title" x-text="titles[service] || ''"></h3>
            </div>
            <button type="button" class="modal-close" @click="closeModal()" aria-label="{{ __('enquiry.close') }}">✕</button>
        </div>

        {{-- Success state --}}
        <div class="modal-success" x-show="sent" x-cloak>
            <div class="success-badge" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 12.5l5 5L20 6.5"></path></svg>
            </div>
            <h4 class="success-title">{{ __('enquiry.success_thanks') }} {{ __('enquiry.success_sub') }}</h4>
            <button type="button" class="btn btn-outline" @click="closeModal()">{{ __('enquiry.close') }}</button>
        </div>

        {{-- Form --}}
        <form class="enquiry-form" x-show="! sent" @submit.prevent="submit($el)" novalidate>
            <label class="field">
                <span class="field-label">{{ __('enquiry.name') }}</span>
                <input type="text" x-model="form.name" :class="{ error: fieldError('name') }"
                       placeholder="{{ __('enquiry.name_placeholder') }}" autocomplete="name">
                <span class="field-error" x-show="fieldError('name')" x-text="fieldError('name')"></span>
            </label>

            <label class="field">
                <span class="field-label">{{ __('enquiry.phone') }}</span>
                <input type="tel" x-model="form.phone" :class="{ error: fieldError('phone') }"
                       placeholder="{{ __('enquiry.phone_placeholder') }}" autocomplete="tel">
                <span class="field-error" x-show="fieldError('phone')" x-text="fieldError('phone')"></span>
            </label>

            <label class="field">
                <span class="field-label">{{ __('enquiry.email') }}</span>
                <input type="email" x-model="form.email" :class="{ error: fieldError('email') }"
                       placeholder="{{ __('enquiry.email_placeholder') }}" autocomplete="email">
                <span class="field-error" x-show="fieldError('email')" x-text="fieldError('email')"></span>
            </label>

            <label class="field">
                <span class="field-label">{{ __('enquiry.message') }}</span>
                <textarea rows="3" x-model="form.message" :class="{ error: fieldError('message') }"
                          placeholder="{{ __('enquiry.message_placeholder') }}"></textarea>
                <span class="field-error" x-show="fieldError('message')" x-text="fieldError('message')"></span>
            </label>

            {{-- Honeypot — invisible to humans, only bots fill it in. --}}
            <div class="hp-field" aria-hidden="true">
                <label>Website <input type="text" x-model="form.website" tabindex="-1" autocomplete="off"></label>
            </div>

            @if (config('services.turnstile.site_key'))
                <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="dark"></div>
            @endif

            <label class="consent-field">
                <input type="checkbox" x-model="form.consent" :class="{ error: fieldError('consent') }">
                <span class="consent-text">{{ __('enquiry.consent_before') }}<a href="{{ loc_route('legal', ['page' => 'datenschutz']) }}" target="_blank" rel="noopener">{{ __('enquiry.consent_link') }}</a>{{ __('enquiry.consent_after') }}</span>
            </label>
            <span class="field-error" x-show="fieldError('consent')" x-text="fieldError('consent')"></span>

            <p class="form-general-error" x-show="generalError()" x-text="generalError()" x-cloak></p>

            <button type="submit" class="btn btn-solid btn-submit" :disabled="loading" :class="{ loading }">
                <span class="spinner" x-show="loading" aria-hidden="true"></span>
                <span x-text="loading ? @js(__('enquiry.sending')) : @js(__('enquiry.submit'))"></span>
            </button>

            <p class="choreo-hint" x-show="service === 'choreo'" x-cloak>{{ __('services.choreo.hint') }}</p>
        </form>
    </div>
</div>
