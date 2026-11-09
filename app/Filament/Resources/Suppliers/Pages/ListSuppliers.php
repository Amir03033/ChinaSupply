<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Enums\Permission;
use App\Filament\Exports\SupplierExporter;
use App\Filament\Imports\SupplierImporter;
use App\Filament\Resources\Suppliers\SupplierResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        $canIo = (bool) auth()->user()?->can(Permission::ImportExportSuppliers->value);

        return [
            ImportAction::make()->importer(SupplierImporter::class)->visible($canIo)->label('Import CSV'),
            ExportAction::make()->exporter(SupplierExporter::class)->visible($canIo)->label('Export'),
            CreateAction::make()->label('New supplier'),
        ];
    }
}
