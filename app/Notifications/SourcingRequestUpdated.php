<?php

namespace App\Notifications;

use App\Models\SourcingRequest;
use App\Support\RequestLinks;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Buyer-facing update: status change, new message from ChinaSupply, or a
 * shared shortlist. Content stays on the platform; the mail only links to it.
 */
class SourcingRequestUpdated extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SourcingRequest $request, public string $kind) {}

    public static function send(SourcingRequest $request, string $kind): void
    {
        $notification = (new self($request, $kind))->locale($request->locale);

        if ($request->user) {
            $request->user->notify($notification);
        } else {
            Notification::route('mail', $request->contact_email)->notify($notification);
        }
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('requests.mail.updated_subject.'.$this->kind, ['reference' => $this->request->reference]))
            ->greeting(__('requests.mail.greeting', ['name' => $this->request->contact_name]))
            ->line(__('requests.mail.updated_line.'.$this->kind, ['status' => $this->request->status->buyerLabel()]))
            ->action(__('requests.mail.view_request'), RequestLinks::forBuyer($this->request));
    }
}
