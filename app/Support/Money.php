<?php

namespace App\Support;

/**
 * Money is stored as integer minor units (cents) everywhere.
 */
class Money
{
    public static function decimalToCents(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round(((float) str_replace(',', '.', (string) $value)) * 100);
    }

    public static function centsToDecimal(?int $cents): ?string
    {
        return $cents === null ? null : number_format($cents / 100, 2, '.', '');
    }

    public static function format(?int $cents, string $currency): ?string
    {
        if ($cents === null) {
            return null;
        }

        return $currency.' '.number_format($cents / 100, 2, app()->getLocale() === 'nl' ? ',' : '.', app()->getLocale() === 'nl' ? '.' : ',');
    }

    public static function range(?int $min, ?int $max, string $currency): ?string
    {
        if ($min === null && $max === null) {
            return null;
        }
        if ($min !== null && $max !== null && $min !== $max) {
            return self::format($min, $currency).' – '.number_format($max / 100, 2, app()->getLocale() === 'nl' ? ',' : '.', app()->getLocale() === 'nl' ? '.' : ',');
        }

        return self::format($min ?? $max, $currency);
    }
}
