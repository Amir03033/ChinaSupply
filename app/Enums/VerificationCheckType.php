<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Individual verification checks. Levels are derived from these by
 * VerificationLevelCalculator; nothing else may set a level.
 */
enum VerificationCheckType: string implements HasLabel
{
    case CompanyIdentity = 'company_identity';
    case Contact = 'contact';
    case WebsiteDomain = 'website_domain';
    case BusinessRegistration = 'business_registration';
    case BusinessAddress = 'business_address';
    case FactoryExistence = 'factory_existence';
    case Certifications = 'certifications';
    case ProductionCapabilities = 'production_capabilities';
    case ExportDocumentation = 'export_documentation';
    case ExternalInspection = 'external_inspection';

    public function getLabel(): string
    {
        return __('verification.checks.'.$this->value.'.name');
    }

    /** Default validity in months when a check passes (admin can override per check). */
    public function defaultValidityMonths(): ?int
    {
        return match ($this) {
            self::Certifications => null, // follows the certificate's own expiry
            self::ExternalInspection => 24,
            default => 12,
        };
    }

    /**
     * Checks whose result depends on these supplier fields. When a supplier
     * changes one of them, the dependent passed checks go back to review.
     *
     * @return list<string>
     */
    public function dependsOnFields(): array
    {
        return match ($this) {
            self::CompanyIdentity => ['legal_name', 'name_local', 'registration_number'],
            self::BusinessRegistration => ['legal_name', 'registration_number'],
            self::BusinessAddress => ['address', 'province', 'city', 'country'],
            self::WebsiteDomain => ['website'],
            self::Contact => ['business_email', 'business_phone'],
            default => [],
        };
    }
}
