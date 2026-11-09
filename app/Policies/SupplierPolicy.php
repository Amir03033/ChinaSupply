<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->can(Permission::ViewSuppliers->value);
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $this->viewAny($user) || $this->isOwner($user, $supplier);
    }

    public function create(User $user): bool
    {
        return $user->isStaff() && $user->can(Permission::ManageSuppliers->value);
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->create($user);
    }

    /** Supplier users editing their own profile via the portal. */
    public function updateOwnProfile(User $user, Supplier $supplier): bool
    {
        return $this->isOwner($user, $supplier) && ! in_array($supplier->pipeline_status->value, ['rejected'], true);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->isAdministrator();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Supplier $supplier): bool
    {
        return $user->isAdministrator();
    }

    private function isOwner(User $user, Supplier $supplier): bool
    {
        return $user->isSupplier() && $supplier->users()->whereKey($user->id)->exists();
    }
}
