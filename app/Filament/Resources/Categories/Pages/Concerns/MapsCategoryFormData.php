<?php

namespace App\Filament\Resources\Categories\Pages\Concerns;

use App\Actions\Categories\SaveCategory;
use App\Models\Category;
use Illuminate\Validation\ValidationException;

trait MapsCategoryFormData
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveCategory(?Category $record, array $data): Category
    {
        $translations = $data['translations'] ?? [];
        unset($data['translations']);

        try {
            return app(SaveCategory::class)->handle($record, $data, $translations);
        } catch (ValidationException $e) {
            // Map action errors onto form fields ("data." prefix in Filament forms).
            throw ValidationException::withMessages(
                collect($e->errors())->mapWithKeys(fn ($messages, $key) => ['data.'.$key => $messages])->all()
            );
        }
    }
}
