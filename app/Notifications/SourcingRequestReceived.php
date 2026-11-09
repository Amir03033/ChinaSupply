<?php

namespace App\Notifications;

use App\Models\SourcingRequest;
use App\Support\RequestLinks;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SourcingRequestReceived extends Notification implements ShouldQueue
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
            ->subject(__('requests.mail.received_subject', ['reference' => $this->request->reference]))
            ->greeting(__('requests.mail.greeting', ['name' => $this->request->contact_name]))
            ->line(__('requests.mail.received_line', ['title' => $this->request->title]))
            ->line(__('requests.mail.received_next'))
            ->action(__('requests.mail.view_request'), RequestLinks::forBuyer($this->request))
            ->line($this->request->user_id ? '' : __('requests.mail.guest_hint'));
    }
}
