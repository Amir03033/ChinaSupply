<?php

namespace App\Filament\Resources\ContactEnquiries;

use App\Enums\ContactTopic;
use App\Enums\EnquiryStatus;
use App\Filament\Resources\ContactEnquiries\Pages\ListContactEnquiries;
use App\Filament\Resources\ContactEnquiries\Pages\ViewContactEnquiry;
use App\Models\ContactEnquiry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ContactEnquiryResource extends Resource
{
    protected static ?string $model = ContactEnquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Leads';

    protected static ?string $navigationLabel = 'Contact enquiries';

    public static function getNavigationBadge(): ?string
    {
        $count = ContactEnquiry::where('status', EnquiryStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('topic')->badge(),
                TextEntry::make('status')->badge(),
                TextEntry::make('created_at')->label('Received')->dateTime(),
                TextEntry::make('name'),
                TextEntry::make('email')->copyable(),
                TextEntry::make('company')->placeholder('—'),
                TextEntry::make('phone')->placeholder('—'),
                TextEntry::make('locale')->badge(),
                TextEntry::make('handler.name')->label('Handled by')->placeholder('—'),
                TextEntry::make('message')->columnSpanFull()->prose(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
                TextColumn::make('topic')->badge(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('company')->searchable()->placeholder('—'),
                TextColumn::make('email')->searchable(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(EnquiryStatus::class)->default(EnquiryStatus::New->value),
                SelectFilter::make('topic')->options(ContactTopic::class),
            ])
            ->recordActions([
                ViewAction::make(),
                self::statusAction(),
            ]);
    }

    public static function statusAction(): Action
    {
        return Action::make('set_status')
            ->label('Set status')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->authorize('update')
            ->schema([
                Select::make('status')->options(EnquiryStatus::class)->required(),
            ])
            ->fillForm(fn (ContactEnquiry $record) => ['status' => $record->status])
            ->action(function (ContactEnquiry $record, array $data) {
                $record->status = $data['status'] instanceof EnquiryStatus ? $data['status'] : EnquiryStatus::from($data['status']);
                $record->handled_by = auth()->id();
                $record->handled_at = now();
                $record->save();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactEnquiries::route('/'),
            'view' => ViewContactEnquiry::route('/{record}'),
        ];
    }
}
