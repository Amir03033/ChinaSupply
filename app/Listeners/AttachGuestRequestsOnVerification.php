<?php

namespace App\Listeners;

use App\Actions\Sourcing\AttachGuestRequests;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

class AttachGuestRequestsOnVerification
{
    public function handle(Verified $event): void
    {
        if ($event->user instanceof User) {
            app(AttachGuestRequests::class)->handle($event->user);
        }
    }
}
