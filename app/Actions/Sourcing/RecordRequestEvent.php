<?php

namespace App\Actions\Sourcing;

use App\Models\SourcingRequest;
use App\Models\SourcingRequestEvent;
use App\Models\User;

class RecordRequestEvent
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function record(SourcingRequest $request, string $type, array $data = [], ?User $by = null, bool $visibleToBuyer = true): SourcingRequestEvent
    {
        return $request->events()->create([
            'type' => $type,
            'data' => $data ?: null,
            'visible_to_buyer' => $visibleToBuyer,
            'user_id' => $by?->id,
        ]);
    }
}
