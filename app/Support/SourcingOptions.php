<?php

namespace App\Support;

class SourcingOptions
{
    public const CUSTOMIZATION = ['none', 'logo', 'oem', 'odm', 'unsure'];

    public const INCOTERMS = ['EXW', 'FOB', 'CIF', 'DAP', 'DDP'];

    public const CURRENCIES = ['EUR', 'USD'];

    public const CERTIFICATIONS = ['CE', 'ROHS', 'REACH', 'EN71', 'LFGB', 'OEKO_TEX', 'FSC', 'UNSURE'];

    public const COUNTRIES = ['NL', 'BE', 'DE', 'FR', 'LU', 'AT', 'DK', 'SE', 'FI', 'IE', 'ES', 'PT', 'IT', 'PL', 'CZ', 'GB', 'CH', 'NO', 'XX'];

    /** @return array<string, string> */
    public static function countries(): array
    {
        return collect(self::COUNTRIES)->mapWithKeys(fn ($c) => [$c => __('requests.countries.'.$c)])->all();
    }
}
