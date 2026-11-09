<?php

namespace App\Filament\Resources\Suppliers\RelationManagers\Concerns;

use App\Enums\Permission;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesSupplierRelation
{
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can(Permission::ViewSuppliers->value);
    }

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->can(static::writePermission()->value);
    }

    protected function supplier(): Supplier
    {
        $owner = $this->getOwnerRecord();

        return $owner instanceof Supplier ? $owner : throw new \LogicException('Supplier relation manager used on '.get_class($owner));
    }

    protected static function writePermission(): Permission
    {
        return Permission::ManageSuppliers;
    }
}
