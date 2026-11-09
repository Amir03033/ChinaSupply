<?php

namespace App\Actions\Suppliers;

use App\Enums\Permission;
use App\Enums\SupplierPipelineStatus as S;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class TransitionPipelineStatus
{
    public function handle(Supplier $supplier, S $to, User $actor, ?string $reason = null): Supplier
    {
        if (! $actor->isStaff() || ! $actor->can(Permission::ManageSuppliers->value)) {
            throw new AuthorizationException;
        }

        if (in_array($to, [S::Verified, S::Published], true)) {
            if (! $actor->can(Permission::PublishSuppliers->value)) {
                throw new AuthorizationException('Only verification staff can mark suppliers verified or published.');
            }
            if ($supplier->verification_level < 1) {
                throw ValidationException::withMessages(['pipeline_status' => 'At least verification level 1 (identity checked) is required.']);
            }
        }

        if ($to === S::Published) {
            $missing = [];
            if ($supplier->categories()->doesntExist()) {
                $missing[] = 'at least one category';
            }
            if (blank($supplier->description('en')) && blank($supplier->description('nl'))) {
                $missing[] = 'a company description';
            }
            if ($missing !== []) {
                throw ValidationException::withMessages(['pipeline_status' => 'Cannot publish: missing '.implode(' and ', $missing).'.']);
            }
        }

        if ($to === S::Rejected && blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A rejection reason is required.']);
        }

        $supplier->pipeline_status = $to;
        $supplier->rejection_reason = $to === S::Rejected ? $reason : null;
        if ($to === S::Published) {
            $supplier->published_at ??= now();
        }
        $supplier->save();

        return $supplier;
    }
}
