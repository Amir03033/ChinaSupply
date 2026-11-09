<?php

namespace App\Filament\Resources\SourcingRequests\RelationManagers;

use App\Filament\Resources\SourcingRequests\RelationManagers\Concerns\OwnedBySourcingRequest;
use App\Models\SourcingRequestEvent;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    use OwnedBySourcingRequest;

    protected static string $relationship = 'events';

    protected static ?string $title = 'Timeline';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->dateTime(),
                TextColumn::make('type')->formatStateUsing(fn (SourcingRequestEvent $record) => $record->describe('en')),
                TextColumn::make('user.name')->label('By')->placeholder('—'),
                IconColumn::make('visible_to_buyer')->label('Buyer sees')->boolean(),
            ]);
    }
}
