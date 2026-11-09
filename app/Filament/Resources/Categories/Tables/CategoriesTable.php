<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Actions\Categories\DeleteCategory;
use App\Enums\PublicationStatus;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        $locale = config('chinasupply.default_locale');

        return $table
            ->modifyQueryUsing(function (Builder $query, ListCategories $livewire) {
                $query->with('translations')->withCount('children');

                // Browse one level at a time; searching spans all levels.
                if (blank($livewire->getTableSearch())) {
                    $query->where('parent_id', $livewire->parent);
                }
            })
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->state(fn (Category $record) => $record->translations->firstWhere('locale', $locale)?->name)
                    ->description(fn (Category $record, ListCategories $livewire) => filled($livewire->getTableSearch()) && $record->depth > 0
                        ? 'Level '.$record->depth : null)
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('translations',
                        fn (Builder $q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%'))),
                TextColumn::make('translations_list')
                    ->label('Languages')
                    ->state(fn (Category $record) => $record->translations->pluck('locale')->sort()->implode(', '))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('status')->badge(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                TextColumn::make('children_count')->label('Subcategories')->numeric(),
            ])
            ->filters([
                SelectFilter::make('status')->options(PublicationStatus::class),
                TernaryFilter::make('is_featured')->label('Featured'),
            ])
            ->recordUrl(fn (Category $record) => CategoryResource::getUrl('index', ['parent' => $record->id]))
            ->recordActions([
                Action::make('children')
                    ->label('Open')
                    ->icon(Heroicon::OutlinedFolderOpen)
                    ->url(fn (Category $record) => CategoryResource::getUrl('index', ['parent' => $record->id])),
                Action::make('view_public')
                    ->label('View')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->visible(fn (Category $record) => $record->status === PublicationStatus::Active)
                    ->url(fn (Category $record) => ($slug = $record->translations->firstWhere('locale', $locale)?->slug)
                        ? localized_route('categories.show', ['slug' => $slug], $locale) : null, shouldOpenInNewTab: true),
                EditAction::make(),
                Action::make('delete')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('delete')
                    ->action(function (Category $record) {
                        try {
                            app(DeleteCategory::class)->handle($record);
                            Notification::make()->title('Category deleted')->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()->title('Cannot delete')->body(collect($e->errors())->flatten()->first())->danger()->send();
                        }
                    }),
            ]);
    }
}
