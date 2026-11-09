<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Enums\Permission;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

/**
 * Read-only audit log. No create/edit/delete is exposed.
 */
class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Audit log';

    protected static ?string $modelLabel = 'activity';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can(Permission::ViewActivityLog->value);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('causer.email')->label('By')->placeholder('System'),
            TextEntry::make('description')->label('Event'),
            TextEntry::make('subject_type')->label('Subject')->formatStateUsing(fn (?string $s, $record) => class_basename((string) $s).' #'.$record->subject_id),
            KeyValueEntry::make('attribute_changes.attributes')->label('New values')->columnSpanFull(),
            KeyValueEntry::make('attribute_changes.old')->label('Old values')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('causer'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('description')->label('Event')->badge(),
                TextColumn::make('subject_type')->label('Subject')->formatStateUsing(fn (?string $s, $record) => class_basename((string) $s).' #'.$record->subject_id),
                TextColumn::make('causer.email')->label('By')->placeholder('System')->searchable(),
            ])
            ->filters([
                SelectFilter::make('subject_type')->label('Subject')->options(fn () => Activity::query()->distinct()->pluck('subject_type')
                    ->filter()->mapWithKeys(fn ($t) => [$t => class_basename($t)])->all()),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListActivityLogs::route('/')];
    }
}
