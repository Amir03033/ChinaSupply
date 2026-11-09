<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Actions\Pages\SavePage;
use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $translations = $data['translations'] ?? [];
        unset($data['translations']);

        return app(SavePage::class)->handle(null, $data, $translations);
    }
}
