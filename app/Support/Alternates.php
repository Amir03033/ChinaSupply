<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * hreflang / language-switch URLs for routes whose parameters are the same
 * in every locale. Pages with per-locale slugs pass their own alternates.
 */
class Alternates
{
    /**
     * @return array<string, string>
     */
    public static function forCurrentRoute(): array
    {
        $route = Route::current();
        $name = $route?->getName();

        if (! $name || ! preg_match('/^([a-z]{2}(?:_[A-Z]{2})?)\.(.+)$/', $name, $m) || ! Locales::isSupported($m[1])) {
            return [];
        }

        // Only real URI parameters: Route::view() adds hidden "view"/"status"
        // defaults that must not leak into URLs as query strings.
        $parameters = array_intersect_key($route->parameters(), array_flip($route->parameterNames()));

        return collect(Locales::codes())
            ->mapWithKeys(fn (string $locale) => [$locale => route($locale.'.'.$m[2], $parameters)])
            ->all();
    }
}
