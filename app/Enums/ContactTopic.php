<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContactTopic: string implements HasLabel
{
    case Sourcing = 'sourcing';
    case Services = 'services';
    case Supplier = 'supplier';
    case General = 'general';

    public function getLabel(): string
    {
        return __('enums.contact_topic.'.$this->value);
    }
}
