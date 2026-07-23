<?php

if (! function_exists('loc_route')) {
    /**
     * Resolve a localised route name. English routes live at the root and use
     * the base name (`home`, `legal`, `enquiry`); German routes live under
     * the `/de` prefix and are registered with a `de.` name prefix. This
     * helper picks the right name based on the locale (defaults to the
     * current app locale), so views can stay locale-agnostic.
     *
     * @param  array<string, mixed>  $parameters
     */
    function loc_route(string $name, array $parameters = [], ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $prefix = $locale === 'de' ? 'de.' : '';

        return route($prefix.$name, $parameters);
    }
}

if (! function_exists('current_route_base')) {
    /**
     * The current route's locale-agnostic base name and parameters — the
     * inputs loc_route() needs to re-resolve the current page in another
     * locale (hreflang alternates, the language switcher).
     *
     * @return array{name: string, params: array<string, mixed>}
     */
    function current_route_base(): array
    {
        $route = request()->route();

        return [
            'name' => preg_replace('/^de\./', '', $route?->getName() ?: 'home'),
            'params' => $route?->parameters() ?? [],
        ];
    }
}
