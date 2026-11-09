<?php

use App\Models\ContactEnquiry;
use App\Notifications\NewContactEnquiry;
use Illuminate\Support\Facades\Notification;

$valid = fn (array $override = []) => $override + [
    'topic' => 'sourcing',
    'name' => 'Lisa de Vries',
    'email' => 'lisa@example.nl',
    'company' => 'Yoga BV',
    'message' => 'Ik zoek 500 yogamatten met eigen logo.',
    'privacy' => '1',
];

it('stores an enquiry and notifies staff without including the message', function () use ($valid) {
    Notification::fake();

    $this->post('/nl/contact', $valid())->assertRedirect('/nl/contact')->assertSessionHas('status');

    $enquiry = ContactEnquiry::sole();
    expect($enquiry->locale)->toBe('nl')->and($enquiry->status->value)->toBe('new');

    Notification::assertSentOnDemand(NewContactEnquiry::class, function ($notification, $channels, $notifiable) {
        $mail = $notification->toMail($notifiable);

        return $notifiable->routes['mail'] === config('chinasupply.mail.staff_notifications')
            && ! str_contains(implode(' ', $mail->introLines), 'yogamatten');
    });
});

it('rejects the honeypot, missing consent and invalid topics', function (array $override, string $field) use ($valid) {
    $this->post('/nl/contact', $valid($override))->assertSessionHasErrors($field);
    expect(ContactEnquiry::count())->toBe(0);
})->with([
    'honeypot' => [['website' => 'spam'], 'website'],
    'privacy' => [['privacy' => null], 'privacy'],
    'topic' => [['topic' => 'hack'], 'topic'],
    'email' => [['email' => 'not-an-email'], 'email'],
]);

it('rate limits the contact form per IP', function () use ($valid) {
    Notification::fake();

    foreach (range(1, 3) as $i) {
        $this->post('/nl/contact', $valid(['email' => "u$i@example.nl"]))->assertRedirect();
    }

    $this->post('/nl/contact', $valid())->assertTooManyRequests();
});
