<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SupplierSource: string implements HasLabel
{
    case Alibaba = 'alibaba';
    case Source1688 = '1688';
    case GlobalSources = 'global_sources';
    case MadeInChina = 'made_in_china';
    case CantonFair = 'canton_fair';
    case FactoryWebsite = 'factory_website';
    case Directory = 'directory';
    case Referral = 'referral';
    case SelfRegistration = 'self_registration';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('enums.supplier_source.'.$this->value);
    }
}
