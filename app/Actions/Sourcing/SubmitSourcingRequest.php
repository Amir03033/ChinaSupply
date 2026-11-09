<?php

namespace App\Actions\Sourcing;

use App\Enums\MatchStatus;
use App\Enums\SourcingRequestStatus;
use App\Models\SourcingRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\NewSourcingRequest;
use App\Notifications\SourcingRequestReceived;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SubmitSourcingRequest
{
    public function __construct(private StoreRequestFile $storeFile) {}

    /**
     * @param  array<string, mixed>  $data  validated wizard data (SourcingRequest::BUYER_FIELDS)
     * @param  list<UploadedFile>  $files
     */
    public function handle(array $data, array $files, ?User $buyer, string $locale, ?string $ip = null): SourcingRequest
    {
        $request = DB::transaction(function () use ($data, $files, $buyer, $locale, $ip) {
            $request = new SourcingRequest(array_intersect_key($data, array_flip(SourcingRequest::BUYER_FIELDS)));
            // Only a logged-in BUYER owns the request directly. Guests are linked
            // later, after verifying the same email address (AttachGuestRequests).
            $request->user_id = $buyer?->isBuyer() ? $buyer->id : null;
            if ($buyer?->isBuyer()) {
                $request->contact_email = $buyer->email;
            }
            $request->locale = $locale;
            $request->status = SourcingRequestStatus::Submitted;
            $request->ip_address = $ip;

            // A preferred supplier is only accepted when it is publicly visible.
            if ($request->preferred_supplier_id && ! Supplier::publiclyVisible()->whereKey($request->preferred_supplier_id)->exists()) {
                $request->preferred_supplier_id = null;
            }
            $request->save();

            foreach (array_slice($files, 0, StoreRequestFile::MAX_FILES) as $file) {
                $this->storeFile->handle($request, $file, $buyer);
            }

            if ($request->preferred_supplier_id) {
                $request->matches()->create(['supplier_id' => $request->preferred_supplier_id, 'status' => MatchStatus::Suggested]);
            }

            RecordRequestEvent::record($request, 'submitted', ['reference' => $request->reference], $buyer);
            $request->tasks()->create(['title' => 'Review sourcing request '.$request->reference, 'due_at' => now()->addWeekdays(2)]);

            return $request;
        });

        Notification::route('mail', $request->contact_email)->notify((new SourcingRequestReceived($request))->locale($locale));
        Notification::route('mail', config('chinasupply.mail.staff_notifications'))->notify(new NewSourcingRequest($request));

        return $request;
    }
}
