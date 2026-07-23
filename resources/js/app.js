import Alpine from 'alpinejs';

const CONSENT_KEY = 'ostrovski_consent';

/**
 * Read the stored consent choice, or null if the visitor hasn't decided.
 */
function readConsent() {
    try {
        return JSON.parse(localStorage.getItem(CONSENT_KEY)) || null;
    } catch {
        return null;
    }
}

/**
 * Google Analytics — loaded only after the visitor grants analytics
 * consent. Until then no Google script is requested and no GA cookie is
 * set. The tag id is exposed by the layout as `window.ostrovskiGaId`.
 */
function loadAnalytics() {
    const id = window.ostrovskiGaId;

    if (! id || window.ostrovskiAnalyticsLoaded) {
        return;
    }

    window.ostrovskiAnalyticsLoaded = true;

    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`;
    document.head.appendChild(script);

    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', id);
}

/**
 * Stop Google Analytics after the visitor withdraws consent: flip GA's
 * own opt-out flag so no further hits are sent, and drop the GA cookies.
 * Takes effect immediately, without a page reload.
 */
function disableAnalytics() {
    const id = window.ostrovskiGaId;

    if (id) {
        window[`ga-disable-${id}`] = true;
    }

    // gtag sets its cookies on the registrable domain (e.g.
    // `.ostrovski.studio` even when the site runs on www) — expire every
    // parent-domain variant, not just the current hostname.
    const parts = location.hostname.split('.');
    const domains = [];
    for (let i = 0; i < parts.length - 1; i++) {
        domains.push(parts.slice(i).join('.'));
    }

    document.cookie.split(';').forEach((cookie) => {
        const name = cookie.split('=')[0].trim();

        if (name.startsWith('_ga')) {
            const expired = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
            document.cookie = expired;
            domains.forEach((domain) => {
                document.cookie = `${expired}; domain=${domain}`;
                document.cookie = `${expired}; domain=.${domain}`;
            });
        }
    });
}

// Honour a choice made on an earlier visit.
if (readConsent()?.analytics === true) {
    loadAnalytics();
}

/**
 * Cookie consent banner (GDPR). Stores the visitor's choice in
 * localStorage and, when analytics is accepted, loads Google Analytics
 * immediately — without waiting for a page reload.
 */
Alpine.data('cookieConsent', () => ({
    visible: false,
    expanded: false,
    analytics: false,

    init() {
        const consent = readConsent();
        this.visible = ! consent;
        this.analytics = consent?.analytics === true;

        // Re-open the banner — dispatched by the footer "Cookie settings"
        // button.
        window.addEventListener('cookie-settings', () => this.reopen());
    },

    reopen() {
        this.expanded = true;
        this.visible = true;
    },

    // Close the preferences sheet without persisting anything — a
    // click-away is not a choice. Restores the toggle to the stored state
    // and hides the banner unless the visitor still hasn't decided.
    dismiss() {
        const consent = readConsent();
        this.analytics = consent?.analytics === true;
        this.expanded = false;
        this.visible = ! consent;
    },

    persist(analytics) {
        localStorage.setItem(CONSENT_KEY, JSON.stringify({
            necessary: true,
            analytics,
            at: new Date().toISOString(),
        }));
        this.analytics = analytics;
        this.visible = false;
        this.expanded = false;

        if (analytics) {
            loadAnalytics();
        } else {
            disableAnalytics();
        }
    },

    acceptAll() {
        this.persist(true);
    },

    reject() {
        this.persist(false);
    },

    saveChoice() {
        this.persist(this.analytics);
    },
}));

/**
 * The booking-enquiry modal. One component serves every service — triggers
 * anywhere on the page open it via a window `enquiry-open` event carrying
 * { service }. The form submits over fetch as JSON; the success state is
 * shown inline.
 */
Alpine.data('enquiry', (config) => ({
    url: config.url,
    titles: config.titles,
    errorText: config.errorText,
    expiredText: config.expiredText,
    visible: false,
    service: null,
    loading: false,
    sent: false,
    errors: {},
    form: {},

    openModal(detail) {
        this.service = detail.service;
        this.sent = false;
        this.loading = false;
        this.errors = {};
        this.form = {
            name: '',
            phone: '',
            email: '',
            message: '',
            website: '',
        };
        this.visible = true;
        document.body.style.overflow = 'hidden';
    },

    closeModal() {
        this.visible = false;
        document.body.style.overflow = '';
    },

    /** First validation message for a field, or undefined. */
    fieldError(field) {
        const messages = this.errors[field];
        return Array.isArray(messages) ? messages[0] : messages;
    },

    /** Form-level error — a generic failure or a failed anti-spam check. */
    generalError() {
        return this.errors._
            || this.fieldError('cf-turnstile-response')
            || this.fieldError('website')
            || this.fieldError('service')
            || '';
    },

    async submit(formEl) {
        if (this.loading) {
            return;
        }
        this.loading = true;
        this.errors = {};

        const payload = { ...this.form, service: this.service };
        const turnstile = formEl.querySelector('[name="cf-turnstile-response"]');
        if (turnstile) {
            payload['cf-turnstile-response'] = turnstile.value;
        }

        try {
            const response = await fetch(this.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                this.sent = true;
            } else if (response.status === 422) {
                const data = await response.json();
                this.errors = data.errors || {};
            } else if (response.status === 419) {
                this.errors = { _: this.expiredText };
            } else {
                this.errors = { _: this.errorText };
            }
        } catch (error) {
            this.errors = { _: this.errorText };
        } finally {
            this.loading = false;
            this.resetTurnstile(formEl);
        }
    },

    /**
     * Turnstile tokens are single-use — Cloudflare consumes them on the
     * server-side `siteverify` call. Without a reset, a second submit from
     * the same modal resends the consumed token and fails validation.
     * `turnstile.reset()` re-runs the challenge and stages a fresh token
     * in the same widget.
     */
    resetTurnstile(formEl) {
        const widget = formEl.querySelector('.cf-turnstile');
        if (!widget || !window.turnstile) {
            return;
        }
        try {
            window.turnstile.reset(widget);
        } catch (e) {
            // Widget hasn't finished rendering yet — nothing to reset.
        }
    },
}));

/**
 * The mobile burger menu. State lives on the layout's `.app` wrapper so
 * the overlay (teleported to <body> to escape the fixed topbar's stacking
 * context) and the background regions can all react to it.
 *
 * While open: the page background (main, footer) is made inert so keyboard
 * focus can't wander behind the overlay, and the scroll lock is applied to
 * <html> — locking <body> would be a no-op because `html { overflow-x:
 * hidden }` stops body's overflow from propagating to the viewport.
 */
Alpine.data('mobileMenu', () => ({
    open: false,

    toggle() {
        this.open = ! this.open;
    },

    close() {
        this.open = false;
    },

    init() {
        this.$watch('open', (open) => {
            document.documentElement.style.overflow = open ? 'hidden' : '';
            document.querySelectorAll('main, .footer').forEach((el) => {
                el.inert = open;
            });
            // Return focus to the burger when the menu closes (Escape,
            // resize, or a link tap), so keyboard users aren't dropped to
            // the top of the document.
            if (! open) {
                this.$refs.burger?.focus();
            }
        });
    },
}));

window.Alpine = Alpine;

Alpine.start();
