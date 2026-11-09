<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DocumentType: string implements HasLabel
{
    case BusinessLicense = 'business_license';
    case ExportLicense = 'export_license';
    case Certificate = 'certificate';
    case AuditReport = 'audit_report';
    case FactoryPhoto = 'factory_photo';
    case ProductCatalog = 'product_catalog';
    case Evidence = 'evidence'; // staff-only verification evidence
    case Other = 'other';

    public function getLabel(): string
    {
        return __('enums.document_type.'.$this->value);
    }

    /** Types a supplier may upload themselves. */
    public static function supplierUploadable(): array
    {
        return [self::BusinessLicense, self::ExportLicense, self::Certificate, self::AuditReport, self::FactoryPhoto, self::ProductCatalog, self::Other];
    }
}
