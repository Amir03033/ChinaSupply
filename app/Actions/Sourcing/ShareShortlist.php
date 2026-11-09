<?php

namespace App\Actions\Sourcing;

use App\Enums\MatchStatus;
use App\Enums\Permission;
use App\Enums\SourcingRequestStatus;
use App\Models\SourcingRequest;
use App\Models\User;
use App\Notifications\SourcingRequestUpdated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShareShortlist
{
    /**
     * @param  list<int>  $matchIds
     */
    public function handle(SourcingRequest $request, array $matchIds, User $staff, ?string $note = null): SourcingRequest
    {
        if (! $staff->isStaff() || ! $staff->can(Permission::ManageSourcingRequests->value)) {
            throw new AuthorizationException;
        }

        $matches = $request->matches()->whereIn('id', $matchIds)->with('supplier')->get();
        if ($matches->isEmpty()) {
            throw ValidationException::withMessages(['matches' => 'Select at least one supplier to share.']);
        }

        // Never share a supplier that is not publicly visible (unverified / unpublished).
        $hidden = $matches->reject(fn ($m) => $m->supplier->isPubliclyVisible());
        if ($hidden->isNotEmpty()) {
            throw ValidationException::withMessages(['matches' => 'Not published or not verified: '.$hidden->pluck('supplier.name')->implode(', ').'.']);
        }

        DB::transaction(function () use ($request, $matches, $staff, $note) {
            foreach ($matches as $match) {
                $match->shared_with_buyer = true;
                if (in_array($match->status, [MatchStatus::Suggested, MatchStatus::Contacted, MatchStatus::Quoted], true)) {
                    $match->status = MatchStatus::Shortlisted;
                }
                $match->save();
            }
            $request->status = SourcingRequestStatus::ShortlistReady;
            $request->save();
            RecordRequestEvent::record($request, 'shortlist_shared', ['count' => $request->matches()->where('shared_with_buyer', true)->count()], $staff);
            if (filled($note)) {
                $request->messages()->create(['user_id' => $staff->id, 'from_staff' => true, 'body' => $note]);
            }
        });

        SourcingRequestUpdated::send($request, 'shortlist');

        return $request;
    }
}
