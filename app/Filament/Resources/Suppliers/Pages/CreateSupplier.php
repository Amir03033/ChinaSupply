<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Actions\Suppliers\SaveSupplier;
use App\Filament\Resources\Suppliers\SupplierResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $translations = $data['translations'] ?? [];
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['translations'], $data['category_ids']);
        $data['assigned_to'] ??= auth()->id();

        return app(SaveSupplier::class)->handle(null, $data, $translations, $categoryIds);
    }
}
