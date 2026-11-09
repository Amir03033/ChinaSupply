<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Enums\Permission;
use App\Filament\Resources\Suppliers\RelationManagers\Concerns\AuthorizesSupplierRelation;
use App\Models\Certification;
use App\Models\SupplierCertification;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CertificationsRelationManager extends RelationManager
{
    use AuthorizesSupplierRelation;

    protected static string $relationship = 'certifications';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('certification_id')->label('Standard')->options(fn () => Certification::orderBy('code')->pluck('name', 'id'))
                ->searchable()->live()
                ->afterStateUpdated(fn ($state, Set $set) => $set('name', Certification::find($state)?->name)),
            TextInput::make('name')->required()->maxLength(190),
            TextInput::make('certificate_number')->maxLength(120),
            TextInput::make('issued_by')->maxLength(190),
            DatePicker::make('valid_until'),
            Select::make('document_id')->label('Certificate document')
                ->options(fn () => $this->supplier()->documents()->pluck('original_name', 'id')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('certificate_number')->placeholder('—'),
                TextColumn::make('issued_by')->placeholder('—'),
                TextColumn::make('valid_until')->date()->placeholder('—')->color(fn (SupplierCertification $c) => $c->valid_until?->isPast() ? 'danger' : null),
                TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'verified' => 'success', 'rejected' => 'danger', default => 'warning'
                }),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                Action::make('verify')->label('Mark verified')->icon(Heroicon::OutlinedCheckBadge)->color('success')
                    ->visible(fn (SupplierCertification $c) => $c->status !== 'verified' && auth()->user()?->can(Permission::ManageVerification->value))
                    ->requiresConfirmation()->modalDescription('Only after checking the certificate number with the issuing body.')
                    ->action(fn (SupplierCertification $c) => $c->forceFill(['status' => 'verified'])->save()),
                Action::make('reject')->icon(Heroicon::OutlinedXCircle)->color('danger')
                    ->visible(fn (SupplierCertification $c) => $c->status !== 'rejected' && auth()->user()?->can(Permission::ManageVerification->value))
                    ->requiresConfirmation()
                    ->action(fn (SupplierCertification $c) => $c->forceFill(['status' => 'rejected'])->save()),
                EditAction::make()->after(fn (SupplierCertification $c) => $c->wasChanged(['certificate_number', 'valid_until', 'name']) ? $c->forceFill(['status' => 'pending'])->save() : null),
                DeleteAction::make(),
            ]);
    }
}
