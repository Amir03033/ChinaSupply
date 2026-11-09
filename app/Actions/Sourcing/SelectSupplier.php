<?php

namespace App\Actions\Sourcing;

use App\Enums\MatchStatus;
use App\Models\SourcingRequestMatch;
use App\Models\User;
use App\Notifications\BuyerRepliedToRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;

/**
 * The buyer chooses a supplier from the shared shortlist. ChinaSupply then
 * arranges the introduction; the purchase is agreed directly with the supplier.
 */
class SelectSupplier
{
    public function handle(SourcingRequestMatch $match, User $buyer): SourcingRequestMatch
    {
        $request = $match->sourcingRequest;

        if (! $request->belongsToBuyer($buyer) || ! $match->shared_with_buyer || ! $request->status->isOpen()) {
            throw new AuthorizationException;
        }

        $match->status = MatchStatus::Selected;
        $match->save();

        RecordRequestEvent::record($request, 'supplier_selected', ['supplier' => $match->supplier->name], $buyer);
        $to = $request->assignee->email ?? config('chinasupply.mail.staff_notifications');
        Notification::route('mail', $to)->notify(new BuyerRepliedToRequest($request));

        return $match;
    }
}
