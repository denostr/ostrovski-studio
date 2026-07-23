<footer class="footer">
    <div class="footer-top">
        <div class="footer-brand">
            <div class="footer-logo">OSTROVSKI</div>
            <p class="footer-roles">{{ __('home.hero.roles') }}</p>
        </div>

        <div class="footer-cols">
            <div class="footer-col">
                <span class="footer-col-label">{{ __('footer.legal') }}</span>
                @foreach (config('ostrovski.legal') as $page)
                    <a href="{{ loc_route('legal', ['page' => $page]) }}">{{ __('legal.'.$page.'.title') }}</a>
                @endforeach
            </div>
            <div class="footer-col">
                <span class="footer-col-label">{{ __('footer.follow') }}</span>
                <a class="footer-social" href="{{ config('ostrovski.social.instagram') }}" target="_blank" rel="noopener">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="2.5" y="2.5" width="19" height="19" rx="5"></rect><circle cx="12" cy="12" r="4.2"></circle><circle cx="17.6" cy="6.4" r="1.1" fill="currentColor" stroke="none"></circle></svg>
                    Instagram
                </a>
                @if (config('ostrovski.analytics.ga_id'))
                    <button type="button" class="footer-cookie-link"
                            @click="window.dispatchEvent(new CustomEvent('cookie-settings'))">
                        {{ __('footer.cookie_settings') }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <span>© {{ date('Y') }} Katya Ostrovski</span>
        <span>{{ __('footer.based') }}</span>
    </div>
</footer>
