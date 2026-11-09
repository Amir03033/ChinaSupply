<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied to every web request; locale-prefixed routes override it afterwards.
 */
class SetLocaleFromSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale')
            ?? $request->user()?->getAttribute('locale')
            ?? Locales::preferred($request);

        if (Locales::isSupported($locale)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
