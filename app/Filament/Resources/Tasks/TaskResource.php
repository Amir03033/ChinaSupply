<?php

namespace App\Filament\Resources\Tasks;

use App\Enums\Permission;
use App\Enums\UserType;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Models\Supplier;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Suppliers';

    protected static ?string $navigationLabel = 'Follow-ups';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can(Permission::ViewSuppliers->value);
    }

    public static function canCreate(): bool
    {
        return false; // tasks are created on the record they belong to
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Task::open()->where('assigned_to', auth()->id())->where('due_at', '<=', now()->endOfDay())->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['assignee', 'taskable']))
            ->defaultSort('due_at')
            ->columns([
                TextColumn::make('due_at')->dateTime()->sortable()->color(fn (Task $t) => $t->isOverdue() ? 'danger' : null),
                TextColumn::make('title')->wrap()->searchable(),
                TextColumn::make('taskable')->label('For')
                    ->state(fn (Task $t) => $t->taskable instanceof Supplier ? $t->taskable->name : class_basename($t->taskable_type).' #'.$t->taskable_id)
                    ->url(fn (Task $t) => $t->taskable instanceof Supplier ? SupplierResource::getUrl('edit', ['record' => $t->taskable]) : null),
                TextColumn::make('assignee.name')->label('Assigned to')->placeholder('—'),
            ])
            ->filters([
                Filter::make('mine')->label('Assigned to me')->default()->query(fn (Builder $query) => $query->where('assigned_to', auth()->id())),
                TernaryFilter::make('open')->label('Open only')->default(true)
                    ->queries(true: fn ($query) => $query->whereNull('completed_at'), false: fn ($query) => $query->whereNotNull('completed_at'), blank: fn ($query) => $query),
                SelectFilter::make('assigned_to')->label('Assigned to')->options(fn () => User::where('type', UserType::Staff)->pluck('name', 'id')),
            ])
            ->recordActions([
                Action::make('complete')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Task $t) => $t->completed_at === null && auth()->user()?->can(Permission::ManageSuppliers->value))
                    ->action(fn (Task $t) => $t->update(['completed_at' => now()])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListTasks::route('/')];
    }
}
