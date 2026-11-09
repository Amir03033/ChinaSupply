<?php

namespace App\Policies;

use App\Enums\DocumentType;
use App\Enums\Permission;
use App\Models\SupplierDocument;
use App\Models\User;

class SupplierDocumentPolicy
{
    /**
     * Staff with supplier access may download any document. A supplier may
     * download its own uploads, but never staff verification evidence.
     */
    public function download(User $user, SupplierDocument $document): bool
    {
        if ($user->isStaff()) {
            return $user->can(Permission::ViewSuppliers->value);
        }

        return $user->isSupplier()
            && $document->type !== DocumentType::Evidence
            && $document->supplier->users()->whereKey($user->id)->exists();
    }

    public function review(User $user, SupplierDocument $document): bool
    {
        return $user->isStaff() && $user->can(Permission::ManageVerification->value);
    }

    public function delete(User $user, SupplierDocument $document): bool
    {
        if ($user->isStaff()) {
            return $user->can(Permission::ManageVerification->value);
        }

        // Suppliers can withdraw their own documents only while they are unreviewed.
        return $this->download($user, $document) && $document->status->value === 'pending';
    }
}
