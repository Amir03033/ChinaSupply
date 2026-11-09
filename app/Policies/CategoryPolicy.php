<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->can(Permission::ViewCategories->value);
    }

    public function view(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isStaff() && $user->can(Permission::ManageCategories->value);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->create($user);
    }

    public function reorder(User $user): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->create($user);
    }

    public function deleteAny(User $user): bool
    {
        return false; // deletions go one by one through DeleteCategory (child checks)
    }
}
