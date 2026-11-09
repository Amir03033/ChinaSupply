<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MatchStatus: string implements HasColor, HasLabel
{
    case Suggested = 'suggested';
    case Contacted = 'contacted';
    case Quoted = 'quoted';
    case Declined = 'declined';      // supplier declined or not suitable
    case Shortlisted = 'shortlisted';
    case Selected = 'selected';      // buyer chose this supplier

    public function getLabel(): string
    {
        return __('enums.match_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Suggested => 'gray',
            self::Contacted => 'info',
            self::Quoted => 'primary',
            self::Declined => 'danger',
            self::Shortlisted, self::Selected => 'success',
        };
    }
}
