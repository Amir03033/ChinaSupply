<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VerificationCheckStatus: string implements HasColor, HasLabel
{
    case NotChecked = 'not_checked';
    case Pending = 'pending';   // evidence received / needs (re-)review
    case Passed = 'passed';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return __('enums.check_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Passed => 'success',
            self::Failed => 'danger',
            self::Pending => 'warning',
            self::NotChecked => 'gray',
        };
    }
}
