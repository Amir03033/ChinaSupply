<?php

use App\Support\Locales;

if (! function_exists('localized_route')) {
    /**
     * Route URL for a public, locale-prefixed route ("home", "categories.show", ...).
     *
     * @param  array<string, mixed>|string|int  $parameters
     */
    function localized_route(string $name, array|string|int $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        $locale = Locales::isSupported($locale) ? $locale : app()->getLocale();

        return route($locale.'.'.$name, $parameters, $absolute);
    }
}
