<?php

namespace App\Notifications;

use App\Models\SourcingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSourcingRequest extends Notification implements ShouldQueue
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
            ->subject('New sourcing request '.$this->request->reference)
            ->line('A new sourcing request was submitted: '.$this->request->title.' ('.number_format($this->request->quantity).' '.($this->request->quantity_unit ?? 'units').').')
            ->action('Open in admin', url('/admin/sourcing-requests/'.$this->request->id.'/edit'));
    }
}
