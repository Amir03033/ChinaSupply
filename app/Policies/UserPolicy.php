<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->can(Permission::ManageUsers->value);
    }

    public function view(User $user, User $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, User $model): bool
    {
        // Never allow deleting yourself (avoids locking the platform out).
        return $this->viewAny($user) && $user->isNot($model);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
