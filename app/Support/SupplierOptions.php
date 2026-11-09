<?php

namespace App\Support;

/**
 * Small fixed option lists. Kept in code because they are not business
 * content (unlike categories, which are managed in Filament).
 */
class SupplierOptions
{
    /** @return array<string, string> */
    public static function employeeRanges(): array
    {
        return ['1-49' => '1–49', '50-199' => '50–199', '200-499' => '200–499', '500-999' => '500–999', '1000+' => '1000+'];
    }

    /** @return array<string, string> */
    public static function countries(): array
    {
        return ['CN' => 'China', 'HK' => 'Hong Kong', 'TW' => 'Taiwan', 'VN' => 'Vietnam', 'OTHER' => 'Other'];
    }

    /** @return array<string, string> */
    public static function exportMarkets(): array
    {
        return collect(['eu', 'uk', 'north_america', 'latin_america', 'middle_east', 'africa', 'asia', 'oceania'])
            ->mapWithKeys(fn ($k) => [$k => __('suppliers.markets.'.$k)])->all();
    }

    /** @return array<string, string> */
    public static function languages(): array
    {
        return collect(['en', 'zh', 'nl', 'de', 'fr', 'es', 'ru', 'ar'])
            ->mapWithKeys(fn ($k) => [$k => __('suppliers.languages.'.$k)])->all();
    }
}
