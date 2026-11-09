<?php

namespace App\Filament\Exports;

use App\Models\Supplier;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class SupplierExporter extends Exporter
{
    protected static ?string $model = Supplier::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('name'),
            ExportColumn::make('name_local'),
            ExportColumn::make('legal_name'),
            ExportColumn::make('registration_number'),
            ExportColumn::make('company_type')->formatStateUsing(fn ($state) => $state?->value),
            ExportColumn::make('province'),
            ExportColumn::make('city'),
            ExportColumn::make('website'),
            ExportColumn::make('business_email'),
            ExportColumn::make('business_phone'),
            ExportColumn::make('moq_min'),
            ExportColumn::make('offers_oem'),
            ExportColumn::make('offers_odm'),
            ExportColumn::make('pipeline_status')->formatStateUsing(fn ($state) => $state?->value),
            ExportColumn::make('verification_level'),
            ExportColumn::make('source')->formatStateUsing(fn ($state) => $state?->value),
            ExportColumn::make('source_url'),
            ExportColumn::make('assignee.name')->label('owner'),
            ExportColumn::make('last_contacted_at'),
            ExportColumn::make('next_follow_up_at'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Supplier export completed: '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';
    }
}
