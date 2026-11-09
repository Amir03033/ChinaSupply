<?php

namespace App\Filament\Resources\SourcingRequests\RelationManagers\Concerns;

use App\Enums\Permission;
use App\Models\SourcingRequest;
use Illuminate\Database\Eloquent\Model;

trait OwnedBySourcingRequest
{
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can(Permission::ManageSourcingRequests->value);
    }

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->can(Permission::ManageSourcingRequests->value);
    }

    protected function sourcingRequest(): SourcingRequest
    {
        $owner = $this->getOwnerRecord();

        return $owner instanceof SourcingRequest ? $owner : throw new \LogicException;
    }
}
