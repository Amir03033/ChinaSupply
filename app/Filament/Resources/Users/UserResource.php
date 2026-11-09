<?php

namespace App\Filament\Resources\Users;

use App\Enums\StaffRole;
use App\Enums\UserType;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()->revealable()
                    ->required(fn (?User $record) => $record === null)
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->rule(Password::min(12)->mixedCase()->numbers())
                    ->helperText(fn (?User $record) => $record ? 'Leave empty to keep the current password.' : 'Staff must set up two-factor authentication at first login.'),
                TextInput::make('type_label')->label('Account type')
                    ->formatStateUsing(fn (?User $record) => $record?->type->label() ?? UserType::Staff->label())
                    ->disabled()->dehydrated(false),
                CheckboxList::make('roles')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => StaffRole::tryFrom($record->name)?->label() ?? $record->name)
                    ->visible(fn (?User $record) => $record === null || $record->isStaff())
                    ->required(fn (?User $record) => $record === null)
                    ->columnSpanFull()
                    ->helperText('Administrator: everything. Sourcing manager: leads and sourcing. Verification specialist: supplier verification.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('roles'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('type')->badge()->formatStateUsing(fn (UserType $state) => $state->label()),
                TextColumn::make('roles.name')->badge()->label('Roles')
                    ->formatStateUsing(fn (string $state) => StaffRole::tryFrom($state)?->label() ?? $state),
                TextColumn::make('last_login_at')->since()->placeholder('Never'),
                TextColumn::make('created_at')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(collect(UserType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
