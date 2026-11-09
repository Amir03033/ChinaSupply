<?php

namespace App\Filament\Resources\SourcingRequests\RelationManagers;

use App\Filament\Resources\SourcingRequests\RelationManagers\Concerns\OwnedBySourcingRequest;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotesRelationManager extends RelationManager
{
    use OwnedBySourcingRequest;

    protected static string $relationship = 'notes';

    protected static ?string $title = 'Internal notes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([Textarea::make('body')->label('Note (internal only)')->required()->rows(5)->maxLength(5000)]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user')->latest())
            ->columns([
                TextColumn::make('body')->wrap()->limit(300),
                TextColumn::make('user.name')->label('By')->placeholder('—'),
                TextColumn::make('created_at')->since(),
            ])
            ->headerActions([CreateAction::make()->label('Add note')->mutateDataUsing(fn (array $data) => $data + ['user_id' => auth()->id()])]);
    }
}
