<?php

namespace App\Support;

use Illuminate\Http\Request;

class Locales
{
    /**
     * @return array<string, string> code => native name
     */
    public static function all(): array
    {
        return config('chinasupply.locales');
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::all());
    }

    public static function default(): string
    {
        return config('chinasupply.default_locale');
    }

    public static function preferred(Request $request): string
    {
        return $request->getPreferredLanguage(self::codes()) ?? self::default();
    }
}
