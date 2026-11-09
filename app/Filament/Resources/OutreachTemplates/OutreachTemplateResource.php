<?php

namespace App\Filament\Resources\OutreachTemplates;

use App\Enums\CommunicationChannel;
use App\Enums\Permission;
use App\Filament\Resources\OutreachTemplates\Pages\ManageOutreachTemplates;
use App\Models\OutreachTemplate;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class OutreachTemplateResource extends Resource
{
    protected static ?string $model = OutreachTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Suppliers';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can(Permission::ViewSuppliers->value);
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageOutreachTemplates->value);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canCreate();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(190),
            Select::make('channel')->options(CommunicationChannel::class)->required()->default(CommunicationChannel::Email->value),
            Select::make('locale')->options(['en' => 'English', 'zh_CN' => 'Simplified Chinese', 'nl' => 'Dutch'])->required()->default('en'),
            Toggle::make('is_active')->default(true),
            TextInput::make('subject')->maxLength(255)->columnSpanFull(),
            Textarea::make('body')->required()->rows(12)->columnSpanFull()
                ->helperText('Placeholders: {company}, {contact_name}, {sender_name}, {company_kvk}, {website}'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('channel')->badge()->color('gray'),
            TextColumn::make('locale')->badge()->color('gray'),
            IconColumn::make('is_active')->boolean(),
            TextColumn::make('updated_at')->since(),
        ])->headerActions([CreateAction::make()])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOutreachTemplates::route('/')];
    }
}
