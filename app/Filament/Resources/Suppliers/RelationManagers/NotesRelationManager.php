<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Filament\Resources\Suppliers\RelationManagers\Concerns\AuthorizesSupplierRelation;
use App\Models\Note;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotesRelationManager extends RelationManager
{
    use AuthorizesSupplierRelation;

    protected static string $relationship = 'notes';

    protected static ?string $title = 'Internal notes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')->label('Note (internal, never shown to suppliers or buyers)')->required()->rows(5)->maxLength(5000),
            Toggle::make('is_pinned')->label('Pin'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user')->orderByDesc('is_pinned')->latest())
            ->columns([
                IconColumn::make('is_pinned')->label('')->boolean()->trueIcon('heroicon-s-bookmark')->falseIcon(''),
                TextColumn::make('body')->wrap()->limit(300),
                TextColumn::make('user.name')->label('By')->placeholder('—'),
                TextColumn::make('created_at')->since(),
            ])
            ->headerActions([CreateAction::make()->label('Add note')->mutateDataUsing(fn (array $d) => $d + ['user_id' => auth()->id()])])
            ->recordActions([
                EditAction::make()->visible(fn (Note $n) => $n->user_id === auth()->id()),
                DeleteAction::make()->visible(fn (Note $n) => $n->user_id === auth()->id() || auth()->user()?->isAdministrator()),
            ]);
    }
}
