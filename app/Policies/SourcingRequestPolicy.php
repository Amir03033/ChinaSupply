<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\SourcingRequest;
use App\Models\User;

class SourcingRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->can(Permission::ManageSourcingRequests->value);
    }

    public function view(User $user, SourcingRequest $request): bool
    {
        return $this->viewAny($user) || $request->belongsToBuyer($user);
    }

    public function create(User $user): bool
    {
        return false; // requests come from buyers via the wizard
    }

    public function update(User $user, SourcingRequest $request): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, SourcingRequest $request): bool
    {
        return $user->isAdministrator();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
