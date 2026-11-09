<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SupplierPipelineStatus: string implements HasColor, HasLabel
{
    case Discovered = 'discovered';
    case Researching = 'researching';
    case Applied = 'applied'; // self-registered, onboarding not yet submitted
    case Contacted = 'contacted';
    case Responded = 'responded';
    case Interested = 'interested';
    case DocumentsRequested = 'documents_requested';
    case VerificationPending = 'verification_pending';
    case Verified = 'verified';
    case Published = 'published';
    case Rejected = 'rejected';
    case Inactive = 'inactive';

    public function getLabel(): string
    {
        return __('enums.pipeline_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Published => 'success',
            self::Verified => 'info',
            self::VerificationPending, self::DocumentsRequested => 'warning',
            self::Rejected => 'danger',
            self::Inactive => 'gray',
            default => 'primary',
        };
    }

    /** Statuses shown as Kanban columns, in order. Rejected/inactive are filtered views. */
    public static function board(): array
    {
        return [
            self::Discovered, self::Researching, self::Applied, self::Contacted, self::Responded,
            self::Interested, self::DocumentsRequested, self::VerificationPending, self::Verified, self::Published,
        ];
    }
}
