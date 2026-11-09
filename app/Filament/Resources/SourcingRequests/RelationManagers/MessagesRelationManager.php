<?php

namespace App\Filament\Resources\SourcingRequests\RelationManagers;

use App\Actions\Sourcing\PostRequestMessage;
use App\Filament\Resources\SourcingRequests\RelationManagers\Concerns\OwnedBySourcingRequest;
use App\Models\SourcingRequestMessage;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    use OwnedBySourcingRequest;

    protected static string $relationship = 'messages';

    protected static ?string $title = 'Messages with buyer';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('from_staff')->label('From')->badge()
                    ->formatStateUsing(fn (SourcingRequestMessage $record) => $record->from_staff ? 'ChinaSupply ('.($record->user->name ?? '—').')' : 'Buyer')
                    ->color(fn (SourcingRequestMessage $record) => $record->from_staff ? 'gray' : 'warning'),
                TextColumn::make('body')->wrap()->limit(400),
            ])
            ->headerActions([
                Action::make('reply')->label('Message buyer')->icon(Heroicon::OutlinedPaperAirplane)
                    ->hidden(fn () => $this->isReadOnly())
                    ->schema([Textarea::make('body')->label('Message')->required()->rows(6)->maxLength(5000)])
                    ->action(fn (array $data) => app(PostRequestMessage::class)->handle($this->sourcingRequest(), auth()->user(), $data['body'])),
            ]);
    }
}
