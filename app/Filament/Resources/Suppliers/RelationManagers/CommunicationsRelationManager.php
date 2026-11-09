<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Enums\CommunicationChannel;
use App\Filament\Resources\Suppliers\RelationManagers\Concerns\AuthorizesSupplierRelation;
use App\Models\SupplierCommunication;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommunicationsRelationManager extends RelationManager
{
    use AuthorizesSupplierRelation;

    protected static string $relationship = 'communications';

    protected static ?string $title = 'Communication';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('direction')->options(['outbound' => 'Outbound (we contacted them)', 'inbound' => 'Inbound (they contacted us)'])->required()->default('inbound'),
            Select::make('channel')->options(CommunicationChannel::class)->required()->default(CommunicationChannel::Email->value),
            Select::make('contact_id')->label('Contact')->options(fn () => $this->supplier()->contacts()->pluck('name', 'id')),
            DateTimePicker::make('occurred_at')->required()->default(now()),
            TextInput::make('subject')->maxLength(255)->columnSpanFull(),
            Textarea::make('body')->label('Summary or message')->rows(6)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'contact']))
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('occurred_at')->dateTime()->sortable(),
                TextColumn::make('direction')->badge()->color(fn (string $state) => $state === 'inbound' ? 'success' : 'gray'),
                TextColumn::make('channel')->badge()->color('gray'),
                TextColumn::make('subject')->limit(50)->placeholder('—'),
                TextColumn::make('contact.name')->placeholder('—'),
                TextColumn::make('user.name')->label('By')->placeholder('—'),
            ])
            ->filters([SelectFilter::make('direction')->options(['inbound' => 'Inbound', 'outbound' => 'Outbound'])])
            ->headerActions([
                CreateAction::make()->label('Log communication')
                    ->mutateDataUsing(fn (array $data) => $data + ['user_id' => auth()->id()]),
            ])
            ->recordActions([ViewAction::make()->modalHeading(fn (SupplierCommunication $r) => $r->subject ?? 'Communication')]);
    }
}
