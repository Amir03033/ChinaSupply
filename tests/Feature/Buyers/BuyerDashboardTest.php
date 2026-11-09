<?php

use App\Actions\Sourcing\AttachGuestRequests;
use App\Actions\Sourcing\ChangeRequestStatus;
use App\Actions\Sourcing\ShareShortlist;
use App\Actions\Sourcing\SubmitSourcingRequest;
use App\Enums\MatchStatus;
use App\Enums\SourcingRequestStatus;
use App\Enums\StaffRole;
use App\Enums\UserType;
use App\Models\User;
use App\Notifications\BuyerRepliedToRequest;
use App\Notifications\SourcingRequestUpdated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

it('links guest requests only after the buyer verifies the same email', function () {
    $r = makeRequest(['contact_email' => 'Lisa@Example.nl']);
    $user = User::factory()->unverified()->create(['email' => 'lisa@example.nl']);

    expect(app(AttachGuestRequests::class)->handle($user))->toBe(0);

    $user->markEmailAsVerified();
    event(new Verified($user));
    expect($r->fresh()->user_id)->toBe($user->id);
});

it('never links guest requests to supplier accounts', function () {
    $r = makeRequest(['contact_email' => 'factory@example.cn']);
    $user = User::factory()->create(['email' => 'factory@example.cn', 'type' => UserType::Supplier]);
    event(new Verified($user));

    expect($r->fresh()->user_id)->toBeNull();
});

it('shows buyers only their own requests', function () {
    $buyer = buyerUser();
    $mine = makeRequest([], $buyer);
    $theirs = makeRequest(['contact_email' => 'other@example.nl'], buyerUser());

    $this->actingAs($buyer)->get('/account')->assertOk()->assertSee($mine->title);
    $this->actingAs($buyer)->get('/account/requests')->assertOk()->assertSee($mine->reference)->assertDontSee($theirs->reference);
    $this->actingAs($buyer)->get('/account/requests/'.$mine->reference)->assertOk();
    $this->actingAs($buyer)->get('/account/requests/'.$theirs->reference)->assertNotFound();
});

it('shows only shared matches and never internal notes', function () {
    $buyer = buyerUser();
    $r = makeRequest([], $buyer);
    $shared = publishedSupplier(['name' => 'Shared Factory']);
    $internal = publishedSupplier(['name' => 'Internal Candidate']);
    $m1 = $r->matches()->create(['supplier_id' => $shared->id, 'unit_price_cents' => 250, 'currency' => 'USD', 'advice' => 'Good quality', 'internal_notes' => 'Secret margin note']);
    $r->matches()->create(['supplier_id' => $internal->id]);

    app(ShareShortlist::class)->handle($r, [$m1->id], staffUser(StaffRole::SourcingManager));

    $this->actingAs($buyer)->get('/account/requests/'.$r->reference)
        ->assertSee('Shared Factory')->assertSee('Good quality')
        ->assertDontSee('Internal Candidate')->assertDontSee('Secret margin note');
    expect($r->fresh()->status)->toBe(SourcingRequestStatus::ShortlistReady);
    Notification::assertSentTo($buyer, SourcingRequestUpdated::class);
});

it('refuses to share unpublished suppliers', function () {
    $r = makeRequest();
    $m = $r->matches()->create(['supplier_id' => makeSupplier()->id]);

    app(ShareShortlist::class)->handle($r, [$m->id], staffUser(StaffRole::SourcingManager));
})->throws(ValidationException::class);

it('lets the buyer reply, which reopens a needs-info request and notifies staff', function () {
    $buyer = buyerUser();
    $r = makeRequest([], $buyer);
    app(ChangeRequestStatus::class)->handle($r, SourcingRequestStatus::NeedsInfo, staffUser(StaffRole::SourcingManager), 'Which size?');
    Notification::fake();

    $this->actingAs($buyer)->post('/account/requests/'.$r->reference.'/messages', ['body' => '40 x 40 cm'])->assertRedirect();

    expect($r->fresh()->status)->toBe(SourcingRequestStatus::InReview)
        ->and($r->messages()->count())->toBe(2);
    Notification::assertSentOnDemand(BuyerRepliedToRequest::class);

    $this->actingAs(buyerUser())->post('/account/requests/'.$r->reference.'/messages', ['body' => 'hi'])->assertNotFound();
});

it('lets the buyer choose only a shared supplier on their own request', function () {
    $buyer = buyerUser();
    $r = makeRequest([], $buyer);
    $shared = $r->matches()->create(['supplier_id' => publishedSupplier(['name' => 'A'])->id]);
    $notShared = $r->matches()->create(['supplier_id' => publishedSupplier(['name' => 'B'])->id]);
    app(ShareShortlist::class)->handle($r, [$shared->id], staffUser(StaffRole::SourcingManager));

    $this->actingAs($buyer)->post("/account/requests/{$r->reference}/select/{$notShared->id}")->assertForbidden();
    $this->actingAs($buyer)->post("/account/requests/{$r->reference}/select/{$shared->id}")->assertRedirect();
    expect($shared->fresh()->status)->toBe(MatchStatus::Selected);

    $other = makeRequest(['contact_email' => 'x@example.nl'], buyerUser());
    $this->actingAs($buyer)->post("/account/requests/{$other->reference}/select/{$shared->id}")->assertNotFound();
});

it('only lets staff with the permission change status', function () {
    $r = makeRequest();

    expect(fn () => app(ChangeRequestStatus::class)->handle($r, SourcingRequestStatus::Sourcing, staffUser(StaffRole::VerificationSpecialist)))
        ->toThrow(AuthorizationException::class);

    app(ChangeRequestStatus::class)->handle($r, SourcingRequestStatus::Declined, staffUser(StaffRole::SourcingManager), 'Outside our scope.');
    expect($r->fresh()->closed_at)->not->toBeNull();
    Notification::assertSentOnDemand(SourcingRequestUpdated::class, fn ($n, $ch, $notifiable) => $notifiable->routes['mail'] === 'lisa@example.nl');
});

it('gives guests a signed, read-only tracking page in their language', function () {
    $r = makeRequest();
    $url = URL::temporarySignedRoute('requests.track', now()->addDay(), ['sourcingRequest' => $r->uuid]);

    $this->get($url)->assertOk()->assertSee($r->reference)->assertSee(__('requests.buyer_status.submitted', [], 'nl'))
        ->assertDontSee('name="body"', false);
    $this->get(route('requests.track', $r->uuid))->assertForbidden();

    $this->travel(2)->days();
    $this->get($url)->assertForbidden();
});

it('protects request files', function () {
    Storage::fake('local');
    $buyer = buyerUser();
    $r = app(SubmitSourcingRequest::class)->handle([
        'contact_name' => 'L', 'contact_email' => 'l@example.nl', 'title' => 'T', 'description' => 'Long enough description.', 'customization' => 'none',
        'quantity' => 5, 'currency' => 'EUR', 'destination_country' => 'NL',
    ], [UploadedFile::fake()->createWithContent('s.pdf', "%PDF-1.4\n")], $buyer, 'en');
    $file = $r->files()->sole();

    $this->actingAs($buyer)->get("/account/requests/{$r->reference}/files/{$file->id}")->assertOk();
    $this->actingAs(buyerUser())->get("/account/requests/{$r->reference}/files/{$file->id}")->assertNotFound();
    $this->actingAs(staffUser(StaffRole::SourcingManager))->get(route('staff.request-files.download', $file))->assertOk();
    $this->actingAs(staffUser(StaffRole::VerificationSpecialist))->get(route('staff.request-files.download', $file))->assertForbidden();
});
