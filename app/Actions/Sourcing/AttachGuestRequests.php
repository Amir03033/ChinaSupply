<?php

namespace App\Actions\Sourcing;

use App\Models\SourcingRequest;
use App\Models\User;

/**
 * Requests submitted as a guest are linked to a buyer account only after the
 * buyer has verified that they own the email address used on the request.
 */
class AttachGuestRequests
{
    public function handle(User $user): int
    {
        if (! $user->isBuyer() || ! $user->hasVerifiedEmail()) {
            return 0;
        }

        return SourcingRequest::whereNull('user_id')
            ->whereRaw('LOWER(contact_email) = ?', [mb_strtolower($user->email)])
            ->update(['user_id' => $user->id]);
    }
}
