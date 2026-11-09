<?php

namespace App\Filament\Resources\SourcingRequests;

use App\Enums\SourcingRequestStatus;
use App\Enums\UserType;
use App\Filament\Resources\SourcingRequests\Pages\ListSourcingRequests;
use App\Filament\Resources\SourcingRequests\Pages\ViewSourcingRequest;
use App\Filament\Resources\SourcingRequests\RelationManagers\EventsRelationManager;
use App\Filament\Resources\SourcingRequests\RelationManagers\FilesRelationManager;
use App\Filament\Resources\SourcingRequests\RelationManagers\MatchesRelationManager;
use App\Filament\Resources\SourcingRequests\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\SourcingRequests\RelationManagers\NotesRelationManager;
use App\Models\SourcingRequest;
use App\Models\User;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SourcingRequestResource extends Resource
{
    protected static ?string $model = SourcingRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Leads';

    protected static ?string $navigationLabel = 'Sourcing requests';

    protected static ?int $navigationSort = 0;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getNavigationBadge(): ?string
    {
        $n = SourcingRequest::where('status', SourcingRequestStatus::Submitted)->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'title', 'contact_email', 'company_name'];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(['default' => 1, 'lg' => 3])->components([
            Section::make('Request')->columnSpan(['default' => 1, 'lg' => 2])->columns(2)->schema([
                TextEntry::make('title')->columnSpanFull()->weight('bold'),
                TextEntry::make('description')->columnSpanFull()->prose(),
                TextEntry::make('specifications')->columnSpanFull()->placeholder('—'),
                TextEntry::make('category.id')->label('Category')
                    ->formatStateUsing(fn (SourcingRequest $record) => $record->category?->translated('name', 'en') ?? '—')->placeholder('—'),
                TextEntry::make('materials')->placeholder('—'),
                TextEntry::make('customization')->formatStateUsing(fn (string $state) => __('requests.customization.'.$state.'.label', [], 'en')),
                TextEntry::make('required_certifications')->label('Certifications')->badge()->placeholder('—'),
                TextEntry::make('quantity')->formatStateUsing(fn (SourcingRequest $record) => number_format($record->quantity).' '.($record->quantity_unit ?? 'units')),
                TextEntry::make('target_price_cents')->label('Target price / unit')
                    ->formatStateUsing(fn (SourcingRequest $record) => Money::format($record->target_price_cents, $record->currency))->placeholder('—'),
                TextEntry::make('desired_delivery_date')->date()->placeholder('—'),
                TextEntry::make('destination_country')->label('Destination'),
                TextEntry::make('incoterm')->placeholder('Not sure — advise'),
                TextEntry::make('preferredSupplier.name')->label('Preferred supplier')->placeholder('—'),
                TextEntry::make('additional_requirements')->columnSpanFull()->placeholder('—'),
            ]),
            Section::make('Buyer')->columnSpan(['default' => 1, 'lg' => 1])->schema([
                TextEntry::make('status')->badge(),
                TextEntry::make('reference')->copyable(),
                TextEntry::make('contact_name'),
                TextEntry::make('contact_email')->copyable(),
                TextEntry::make('company_name')->placeholder('—'),
                TextEntry::make('phone')->placeholder('—'),
                TextEntry::make('user.email')->label('Account')->placeholder('Guest (no account yet)'),
                TextEntry::make('locale')->badge(),
                TextEntry::make('assignee.name')->label('Assigned to')->placeholder('Unassigned'),
                TextEntry::make('submitted_at')->dateTime(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('assignee')->withCount([
                'matches',
                'messages as unread_buyer_messages_count' => fn ($query) => $query->where('from_staff', false)->whereNull('read_at'),
            ]))
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('reference')->searchable()->fontFamily('mono')->sortable(),
                TextColumn::make('title')->searchable()->limit(40)
                    ->description(fn (SourcingRequest $record) => trim(($record->company_name ? $record->company_name.' · ' : '').$record->contact_email)),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('quantity')->numeric()->toggleable(),
                TextColumn::make('matches_count')->label('Matches'),
                TextColumn::make('unread_buyer_messages_count')->label('Buyer replies')->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                TextColumn::make('assignee.name')->label('Assigned')->placeholder('—'),
                TextColumn::make('submitted_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->multiple()->options(SourcingRequestStatus::class)
                    ->default(array_map(fn ($s) => $s->value, SourcingRequestStatus::open())),
                SelectFilter::make('assigned_to')->label('Assigned to')
                    ->options(fn () => User::where('type', UserType::Staff)->pluck('name', 'id')),
                Filter::make('mine')->label('Assigned to me')->query(fn (Builder $query) => $query->where('assigned_to', auth()->id())),
                Filter::make('unassigned')->query(fn (Builder $query) => $query->whereNull('assigned_to')),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            MatchesRelationManager::class,
            MessagesRelationManager::class,
            RelationGroup::make('More', [FilesRelationManager::class, EventsRelationManager::class, NotesRelationManager::class]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSourcingRequests::route('/'),
            'view' => ViewSourcingRequest::route('/{record}'),
            'edit' => ViewSourcingRequest::route('/{record}/edit'), // alias: links in staff mails
        ];
    }
}
