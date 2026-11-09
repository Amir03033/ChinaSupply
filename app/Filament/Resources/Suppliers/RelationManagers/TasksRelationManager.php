<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Enums\UserType;
use App\Filament\Resources\Suppliers\RelationManagers\Concerns\AuthorizesSupplierRelation;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    use AuthorizesSupplierRelation;

    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Follow-ups';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->required()->maxLength(190)->columnSpanFull(),
            DateTimePicker::make('due_at')->required()->default(now()->addDays(3)->setTime(9, 0)),
            Select::make('assigned_to')->label('Assigned to')->default(fn () => auth()->id())
                ->options(fn () => User::where('type', UserType::Staff)->pluck('name', 'id')),
            Textarea::make('description')->rows(3)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('assignee'))
            ->defaultSort('due_at')
            ->columns([
                TextColumn::make('title')->wrap(),
                TextColumn::make('due_at')->dateTime()->sortable()->color(fn (Task $t) => $t->isOverdue() ? 'danger' : null),
                TextColumn::make('assignee.name')->label('Assigned to')->placeholder('—'),
                TextColumn::make('completed_at')->label('Done')->since()->placeholder('Open'),
            ])
            ->filters([TernaryFilter::make('open')->label('Open only')->default(true)
                ->queries(true: fn ($query) => $query->whereNull('completed_at'), false: fn ($query) => $query->whereNotNull('completed_at'), blank: fn ($query) => $query)])
            ->headerActions([CreateAction::make()->label('Add follow-up')->mutateDataUsing(fn (array $d) => $d + ['created_by' => auth()->id()])])
            ->recordActions([
                Action::make('complete')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->hidden(fn (Task $t) => $t->completed_at !== null || $this->isReadOnly())
                    ->action(fn (Task $t) => $t->update(['completed_at' => now()])),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
