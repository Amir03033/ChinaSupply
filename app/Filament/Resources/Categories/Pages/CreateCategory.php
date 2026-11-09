<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\Concerns\MapsCategoryFormData;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCategory extends CreateRecord
{
    use MapsCategoryFormData;

    protected static string $resource = CategoryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->saveCategory(null, $data);
    }

    protected function getRedirectUrl(): string
    {
        $parentId = $this->getRecord()->getAttribute('parent_id');

        return CategoryResource::getUrl('index', $parentId ? ['parent' => $parentId] : []);
    }
}
