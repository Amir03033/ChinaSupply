<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }

    /**
     * Indented tree labels for parent selection, using the default-locale name.
     *
     * @return array<int, string>
     */
    public static function treeOptions(?Category $exclude = null): array
    {
        $locale = config('chinasupply.default_locale');

        $all = Category::query()
            ->with(['translations' => fn ($query) => $query->where('locale', $locale)])
            ->get(['id', 'parent_id', 'path', 'depth', 'sort_order']);

        $byParent = $all->groupBy(fn (Category $c) => $c->parent_id ?? 0);
        $options = [];

        $walk = function (int $parentId) use (&$walk, &$options, $byParent, $exclude) {
            foreach ($byParent->get($parentId, collect())->sortBy('sort_order') as $category) {
                if ($exclude && ($category->id === $exclude->id || str_starts_with($category->path, $exclude->childPath()))) {
                    continue;
                }
                $options[$category->id] = str_repeat('— ', $category->depth).($category->translations->first()->name ?? '#'.$category->id);
                $walk($category->id);
            }
        };
        $walk(0);

        return $options;
    }
}
