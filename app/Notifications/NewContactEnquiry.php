<?php

namespace App\Notifications;

use App\Models\ContactEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactEnquiry extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactEnquiry $enquiry) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Staff-facing mail: kept in English like the admin panel.
        return (new MailMessage)
            ->subject('New contact enquiry: '.$this->enquiry->topic->value)
            ->line('From: '.$this->enquiry->name.($this->enquiry->company ? ' ('.$this->enquiry->company.')' : ''))
            ->line('Topic: '.$this->enquiry->topic->value)
            ->action('Open in admin', url('/admin/contact-enquiries/'.$this->enquiry->id))
            ->line('Reply from the admin panel or your mailbox; the message text is not included in this mail.');
    }
}
