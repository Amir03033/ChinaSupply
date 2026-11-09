<?php

namespace App\Actions\Sourcing;

use App\Enums\Permission;
use App\Enums\SourcingRequestStatus;
use App\Models\SourcingRequest;
use App\Models\SourcingRequestMessage;
use App\Models\User;
use App\Notifications\BuyerRepliedToRequest;
use App\Notifications\SourcingRequestUpdated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class PostRequestMessage
{
    public function handle(SourcingRequest $request, User $author, string $body): SourcingRequestMessage
    {
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > 5000) {
            throw ValidationException::withMessages(['body' => __('requests.errors.message_length')]);
        }

        $fromStaff = $author->isStaff();
        if ($fromStaff ? ! $author->can(Permission::ManageSourcingRequests->value) : ! $request->belongsToBuyer($author)) {
            throw new AuthorizationException;
        }

        $message = $request->messages()->create(['user_id' => $author->id, 'from_staff' => $fromStaff, 'body' => $body]);

        if ($fromStaff) {
            SourcingRequestUpdated::send($request, 'message');
        } else {
            // A buyer answering a "needs info" request puts it back in review.
            if ($request->status === SourcingRequestStatus::NeedsInfo) {
                $request->status = SourcingRequestStatus::InReview;
                $request->save();
                RecordRequestEvent::record($request, 'status_changed', ['status' => SourcingRequestStatus::InReview->value], $author);
            }
            $to = $request->assignee->email ?? config('chinasupply.mail.staff_notifications');
            Notification::route('mail', $to)->notify(new BuyerRepliedToRequest($request));
        }

        return $message;
    }
}
