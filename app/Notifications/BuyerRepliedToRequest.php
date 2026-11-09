<?php

namespace App\Notifications;

use App\Models\SourcingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BuyerRepliedToRequest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SourcingRequest $request) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Buyer activity on '.$this->request->reference)
            ->line('The buyer replied or selected a supplier on '.$this->request->reference.'.')
            ->action('Open in admin', url('/admin/sourcing-requests/'.$this->request->id.'/edit'));
    }
}
