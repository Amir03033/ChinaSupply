<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ContactEnquiry;
use App\Models\User;

class ContactEnquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() && $user->can(Permission::ManageContactEnquiries->value);
    }

    public function view(User $user, ContactEnquiry $enquiry): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false; // enquiries only come from the public form
    }

    public function update(User $user, ContactEnquiry $enquiry): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, ContactEnquiry $enquiry): bool
    {
        return $user->isAdministrator();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdministrator();
    }
}
