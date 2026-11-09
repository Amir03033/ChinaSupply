<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyType: string implements HasLabel
{
    case Manufacturer = 'manufacturer';
    case TradingCompany = 'trading_company';
    case Hybrid = 'hybrid';
    case Unknown = 'unknown';

    public function getLabel(): string
    {
        return __('enums.company_type.'.$this->value);
    }
}
