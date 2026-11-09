<?php

namespace App\Actions\Sourcing;

use App\Enums\Permission;
use App\Enums\SourcingRequestStatus;
use App\Models\SourcingRequest;
use App\Models\User;
use App\Notifications\SourcingRequestUpdated;
use Illuminate\Auth\Access\AuthorizationException;

class ChangeRequestStatus
{
    public function handle(SourcingRequest $request, SourcingRequestStatus $to, User $staff, ?string $messageToBuyer = null): SourcingRequest
    {
        if (! $staff->isStaff() || ! $staff->can(Permission::ManageSourcingRequests->value)) {
            throw new AuthorizationException;
        }

        if ($request->status === $to && blank($messageToBuyer)) {
            return $request;
        }

        $from = $request->status;
        $request->status = $to;
        $request->closed_at = $to->isOpen() ? null : ($request->closed_at ?? now());
        $request->assigned_to ??= $staff->id;
        $request->save();

        if ($from !== $to) {
            RecordRequestEvent::record($request, 'status_changed', ['status' => $to->value], $staff);
        }

        if (filled($messageToBuyer)) {
            $request->messages()->create(['user_id' => $staff->id, 'from_staff' => true, 'body' => $messageToBuyer]);
        }

        SourcingRequestUpdated::send($request, 'status');

        return $request;
    }
}
