<?php

namespace App\Support;

use App\Models\SourcingRequest;
use Illuminate\Support\Facades\URL;

class RequestLinks
{
    public const TRACKING_DAYS = 180;

    /**
     * Logged-in buyers go to their dashboard; guests get a signed, expiring,
     * read-only tracking link (no login, no ability to change anything).
     */
    public static function forBuyer(SourcingRequest $request): string
    {
        if ($request->user_id) {
            return route('account.requests.show', $request->reference);
        }

        return URL::temporarySignedRoute('requests.track', now()->addDays(self::TRACKING_DAYS), ['sourcingRequest' => $request->uuid]);
    }
}
