<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied to locale-prefixed public routes: the URL decides the language and
 * the choice is remembered for un-prefixed pages (login, account).
 */
class SetLocaleFromRoute
{
    public function handle(Request $request, Closure $next, string $locale): Response
    {
        app()->setLocale($locale);
        $request->session()->put('locale', $locale);

        return $next($request);
    }
}
