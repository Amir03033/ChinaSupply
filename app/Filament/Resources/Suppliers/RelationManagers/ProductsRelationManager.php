<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Suppliers\RelationManagers\Concerns\AuthorizesSupplierRelation;
use App\Support\Money;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    use AuthorizesSupplierRelation;

    protected static string $relationship = 'products';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(190),
            Select::make('category_id')->label('Category')->options(fn () => CategoryResource::treeOptions())->searchable(),
            Textarea::make('description')->rows(3)->maxLength(3000)->columnSpanFull(),
            TextInput::make('moq')->label('MOQ')->numeric()->minValue(1),
            TextInput::make('moq_unit')->maxLength(30),
            TextInput::make('price_min_cents')->label('Price from')->numeric()->minValue(0)
                ->formatStateUsing(fn ($state) => Money::centsToDecimal($state))->dehydrateStateUsing(fn ($state) => Money::decimalToCents($state)),
            TextInput::make('price_max_cents')->label('Price to')->numeric()->minValue(0)
                ->formatStateUsing(fn ($state) => Money::centsToDecimal($state))->dehydrateStateUsing(fn ($state) => Money::decimalToCents($state)),
            Select::make('currency')->options(['USD' => 'USD', 'EUR' => 'EUR', 'CNY' => 'CNY'])->default('USD')->required(),
            TextInput::make('lead_time_days')->numeric()->minValue(1),
            Toggle::make('customizable'),
            Toggle::make('is_published')->default(true),
            FileUpload::make('image_path')->label('Image')->image()->disk('public')->directory('products')->visibility('public')
                ->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image_path')->label('')->disk('public')->square(),
                TextColumn::make('name'),
                TextColumn::make('moq')->label('MOQ')->placeholder('—'),
                TextColumn::make('price')->state(fn ($record) => Money::range($record->price_min_cents, $record->price_max_cents, $record->currency))->placeholder('—'),
                IconColumn::make('is_published')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
