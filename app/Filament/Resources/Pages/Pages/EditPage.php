<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Actions\Pages\SavePage;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Page $page */
        $page = $this->getRecord();
        $data['translations'] = $page->translations()->get()
            ->mapWithKeys(fn ($t) => [$t->locale => $t->only(['title', 'slug', 'body', 'seo_title', 'seo_description'])])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Page $record */
        $translations = $data['translations'] ?? [];
        unset($data['translations'], $data['key']);

        return app(SavePage::class)->handle($record, $data, $translations);
    }
}
