<?php

namespace App\Filament\Resources\Suppliers;

use App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Resources\Suppliers\RelationManagers\CertificationsRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\CommunicationsRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\TasksRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\VerificationChecksRelationManager;
use App\Filament\Resources\Suppliers\Schemas\SupplierForm;
use App\Filament\Resources\Suppliers\Tables\SuppliersTable;
use App\Models\Supplier;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Suppliers';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SupplierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuppliersTable::configure($table);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'name_local', 'legal_name', 'registration_number', 'website'];
    }

    public static function getRelations(): array
    {
        return [
            VerificationChecksRelationManager::class,
            RelationGroup::make('CRM', [
                CommunicationsRelationManager::class,
                TasksRelationManager::class,
                NotesRelationManager::class,
                ContactsRelationManager::class,
            ]),
            RelationGroup::make('Profile', [
                DocumentsRelationManager::class,
                CertificationsRelationManager::class,
                ProductsRelationManager::class,
            ]),
        ];
    }

    /** Lets admins open soft-deleted suppliers (to restore them); the list uses the Trashed filter. */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}
