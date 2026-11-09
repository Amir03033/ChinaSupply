<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    /** Current tree level being browsed (null = top level). */
    #[Url]
    public ?int $parent = null;

    public function getCurrentParent(): ?Category
    {
        return $this->parent ? Category::with('translations')->find($this->parent) : null;
    }

    public function getTitle(): string
    {
        $parent = $this->getCurrentParent();

        return $parent ? (string) $parent->translated('name', config('chinasupply.default_locale')) : 'Categories';
    }

    public function getSubheading(): ?string
    {
        $parent = $this->getCurrentParent();

        if (! $parent) {
            return 'Top-level categories. Click a row to open its subcategories; drag rows to reorder.';
        }

        $trail = $parent->ancestors()->map(fn (Category $c) => $c->translated('name', config('chinasupply.default_locale')))
            ->push($parent->translated('name', config('chinasupply.default_locale')));

        return 'Categories › '.$trail->implode(' › ');
    }

    protected function getHeaderActions(): array
    {
        $parent = $this->getCurrentParent();

        return [
            Action::make('up')
                ->label('Up one level')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->visible($parent !== null)
                ->url(CategoryResource::getUrl('index', $parent?->parent_id ? ['parent' => $parent->parent_id] : [])),
            Action::make('edit_parent')
                ->label('Edit this category')
                ->color('gray')
                ->visible($parent !== null)
                ->url($parent ? CategoryResource::getUrl('edit', ['record' => $parent]) : null),
            CreateAction::make()
                ->label($parent ? 'New subcategory' : 'New category')
                ->url(CategoryResource::getUrl('create', $parent ? ['parent' => $parent->id] : [])),
        ];
    }
}
