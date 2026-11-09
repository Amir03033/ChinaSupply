<?php

namespace App\Notifications;

use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupplierSubmittedForReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Supplier $supplier) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Supplier application ready for review: '.$this->supplier->name)
            ->line($this->supplier->name.' completed onboarding and submitted its profile for verification.')
            ->action('Review in admin', url('/admin/suppliers/'.$this->supplier->id.'/edit'));
    }
}
