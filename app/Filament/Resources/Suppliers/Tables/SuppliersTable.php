<?php

namespace App\Filament\Resources\Suppliers\Tables;

use App\Enums\CompanyType;
use App\Enums\SupplierPipelineStatus;
use App\Enums\SupplierSource;
use App\Enums\UserType;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Supplier;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('assignee'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable(['name', 'name_local', 'legal_name', 'registration_number'])->sortable()
                    ->description(fn (Supplier $r) => trim(($r->name_local ? $r->name_local.' · ' : '').collect([$r->city, $r->province])->filter()->implode(', '), ' ·')),
                TextColumn::make('pipeline_status')->label('Status')->badge()->sortable(),
                TextColumn::make('verification_level')->label('Level')->badge()->sortable()
                    ->formatStateUsing(fn (int $state) => $state.' · '.__('verification.level_names.'.$state))
                    ->color(fn (int $state) => $state >= 2 ? 'success' : ($state === 1 ? 'info' : 'gray')),
                IconColumn::make('verification_review_required')->label('Review')->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')->trueColor('warning')->falseIcon('')->toggleable(),
                TextColumn::make('company_type')->label('Type')->badge()->color('gray')->toggleable(),
                TextColumn::make('source')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assignee.name')->label('Owner')->placeholder('—')->toggleable(),
                TextColumn::make('next_follow_up_at')->label('Next follow-up')->date()->sortable()
                    ->color(fn (Supplier $r) => $r->next_follow_up_at?->isPast() ? 'danger' : null)->placeholder('—'),
                TextColumn::make('last_contacted_at')->label('Last contact')->since()->sortable()->placeholder('Never')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('pipeline_status')->label('Status')->options(SupplierPipelineStatus::class)->multiple(),
                SelectFilter::make('verification_level')->label('Level')->options([0 => '0', 1 => '1', 2 => '2', 3 => '3']),
                SelectFilter::make('company_type')->options(CompanyType::class),
                SelectFilter::make('source')->options(SupplierSource::class),
                SelectFilter::make('assigned_to')->label('Owner')
                    ->options(fn () => User::where('type', UserType::Staff)->pluck('name', 'id')),
                SelectFilter::make('category')->label('Category')
                    ->options(fn () => CategoryResource::treeOptions())
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn ($query, $id) => $query->whereHas('categories', fn ($c) => $c->whereKey($id)))),
                TernaryFilter::make('verification_review_required')->label('Needs re-verification'),
                Filter::make('follow_up_overdue')->label('Follow-up overdue')
                    ->query(fn (Builder $query) => $query->where('next_follow_up_at', '<', now())),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), RestoreAction::make()]);
    }
}
