<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Locale handling for the public site.
     *
     * English is the default and lives at the root; German lives under the
     * `/de` prefix and its route names carry a `de.` prefix — so the active
     * locale is read off the route name, not a parameter.
     *
     * The middleware also persists the visitor's locale choice in a
     * year-long cookie and, on safe GET requests to the locale-mirrored
     * English routes without an explicit choice, auto-redirects German-IP
     * visitors to the German equivalent — see `germanIpRedirectTarget()`.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName() ?? '';
        $locale = str_starts_with($routeName, 'de.') ? 'de' : 'en';

        // Explicit language switch via `?_lang=xx` (set by the language
        // switcher links) — persist the choice and 302 to a clean URL.
        // GET-only: a POST carrying ?_lang= would be converted to GET by
        // the 302 and the body silently dropped.
        if ($request->isMethod('GET') && ($switch = $this->explicitLocaleSwitch($request))) {
            Cookie::queue(Cookie::forever('locale', $switch, '/'));

            return redirect($request->fullUrlWithoutQuery('_lang'), 302);
        }

        // Auto-redirect German-IP visitors to /de when they land on an
        // English-side page without an explicit choice. A route is
        // redirectable exactly when its `de.` mirror is registered, so SEO
        // endpoints (sitemap, robots) without a /de counterpart are never
        // hijacked into a 404 — the router is the single source of truth.
        if ($locale === 'en'
            && $request->isMethod('GET')
            && $routeName !== ''
            && Route::has('de.'.$routeName)) {
            if ($target = $this->germanIpRedirectTarget($request, $routeName)) {
                return redirect($target, 302);
            }
        }

        // Remember a first visit to a /de/* URL — so the next visit to the
        // root respects it. Only when no choice is stored yet: an explicit
        // `locale=en` must survive someone following a shared /de link,
        // otherwise the geo-redirect re-enables itself against the
        // visitor's expressed preference.
        if ($locale === 'de' && $request->cookie('locale') === null) {
            Cookie::queue(Cookie::forever('locale', 'de', '/'));
        }

        App::setLocale($locale);

        return $next($request);
    }

    /**
     * Read the `?_lang=` switch param if present and valid, else null.
     */
    private function explicitLocaleSwitch(Request $request): ?string
    {
        $lang = $request->query('_lang');

        if (! is_string($lang)) {
            return null;
        }

        return in_array($lang, config('ostrovski.locales'), true) ? $lang : null;
    }

    /**
     * The `/de`-prefixed equivalent of the current English URL if the
     * visitor should be auto-redirected to German, else null.
     *
     * Checks the persisted choice first (cookie), then falls back to the
     * Cloudflare-supplied country header. Production sits behind Cloudflare
     * (see bootstrap/app.php — `trustProxies`), so the header is reliable
     * there; locally it is absent and we serve English.
     */
    private function germanIpRedirectTarget(Request $request, string $routeName): ?string
    {
        $cookie = $request->cookie('locale');

        if ($cookie === 'en') {
            return null;
        }

        $shouldRedirect = $cookie === 'de'
            || ($cookie === null && strtoupper((string) $request->header('CF-IPCountry')) === 'DE');

        if (! $shouldRedirect) {
            return null;
        }

        $url = loc_route($routeName, $request->route()?->parameters() ?? [], 'de');
        $query = $request->getQueryString();

        return $url.($query !== null ? '?'.$query : '');
    }
}
