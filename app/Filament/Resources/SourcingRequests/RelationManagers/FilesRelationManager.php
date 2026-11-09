<?php

namespace App\Filament\Resources\SourcingRequests\RelationManagers;

use App\Filament\Resources\SourcingRequests\RelationManagers\Concerns\OwnedBySourcingRequest;
use App\Models\SourcingRequestFile;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FilesRelationManager extends RelationManager
{
    use OwnedBySourcingRequest;

    protected static string $relationship = 'files';

    protected static ?string $title = 'Buyer files';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('original_name')->label('File')
                ->url(fn (SourcingRequestFile $record) => route('staff.request-files.download', $record), shouldOpenInNewTab: true),
            TextColumn::make('mime'),
            TextColumn::make('size')->formatStateUsing(fn (int $state) => number_format($state / 1024).' KB'),
            TextColumn::make('created_at')->since(),
        ]);
    }
}
