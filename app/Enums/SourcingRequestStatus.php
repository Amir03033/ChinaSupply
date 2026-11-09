<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Internal statuses. Buyers see the simpler buyerLabel().
 */
enum SourcingRequestStatus: string implements HasColor, HasLabel
{
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case NeedsInfo = 'needs_info';
    case Sourcing = 'sourcing';
    case ShortlistReady = 'shortlist_ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Declined = 'declined';

    public function getLabel(): string
    {
        return __('enums.request_status.'.$this->value);
    }

    public function buyerLabel(): string
    {
        return __('requests.buyer_status.'.$this->value);
    }

    /** One plain-language sentence about what happens next, for the buyer. */
    public function buyerNextStep(): string
    {
        return __('requests.next_step.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Submitted => 'info',
            self::InReview, self::Sourcing => 'primary',
            self::NeedsInfo => 'warning',
            self::ShortlistReady, self::Completed => 'success',
            self::Cancelled, self::Declined => 'gray',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Completed, self::Cancelled, self::Declined], true);
    }

    /** @return list<self> */
    public static function open(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => $s->isOpen()));
    }
}
