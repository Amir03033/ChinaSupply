<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\Concerns\MapsCategoryFormData;
use App\Models\Category;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCategory extends EditRecord
{
    use MapsCategoryFormData;

    protected static string $resource = CategoryResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Category $record */
        $record = $this->getRecord();

        $data['translations'] = $record->translations()->get()
            ->mapWithKeys(fn ($t) => [$t->locale => $t->only(['name', 'slug', 'description', 'seo_title', 'seo_description'])])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Category $record */
        return $this->saveCategory($record, $data);
    }

    protected function getRedirectUrl(): ?string
    {
        $parentId = $this->getRecord()->getAttribute('parent_id');

        return CategoryResource::getUrl('index', $parentId ? ['parent' => $parentId] : []);
    }
}
