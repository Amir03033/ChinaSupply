<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Filament\Resources\Suppliers\RelationManagers\Concerns\AuthorizesSupplierRelation;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    use AuthorizesSupplierRelation;

    protected static string $relationship = 'contacts';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(190),
            TextInput::make('position')->maxLength(190),
            TextInput::make('email')->email()->maxLength(190),
            TextInput::make('phone')->tel()->maxLength(50),
            TextInput::make('wechat_id')->label('WeChat ID')->maxLength(100),
            TextInput::make('whatsapp')->maxLength(50),
            Select::make('preferred_language')->options(['en' => 'English', 'zh' => 'Chinese', 'nl' => 'Dutch']),
            Toggle::make('is_primary')->label('Primary contact'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->description(fn ($record) => $record->position),
                TextColumn::make('email')->copyable()->placeholder('—'),
                TextColumn::make('phone')->placeholder('—'),
                TextColumn::make('wechat_id')->label('WeChat')->copyable()->placeholder('—'),
                IconColumn::make('is_primary')->label('Primary')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
