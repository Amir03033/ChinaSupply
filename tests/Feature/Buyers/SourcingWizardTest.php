<?php

use App\Actions\Sourcing\SubmitSourcingRequest;
use App\Enums\MatchStatus;
use App\Enums\SourcingRequestStatus;
use App\Livewire\SourcingWizard;
use App\Models\SourcingRequest;
use App\Notifications\NewSourcingRequest;
use App\Notifications\SourcingRequestReceived;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function fillWizard($component, array $override = [])
{
    $f = $override + [
        'title' => 'Cotton tote bags', 'description' => 'Natural cotton tote bags with a one-colour print of our logo.',
        'customization' => 'logo', 'quantity' => 1000, 'currency' => 'EUR', 'destination_country' => 'NL',
        'contact_name' => 'Mark', 'contact_email' => 'mark@webshop.nl',
    ];
    foreach ($f as $k => $v) {
        $component->set("form.$k", $v);
    }

    return $component;
}

it('renders the wizard in both locales', function () {
    $this->get('/nl/sourcing-aanvraag')->assertOk()->assertSee(__('requests.wizard.title', [], 'nl'));
    $this->get('/en/sourcing-request')->assertOk();
});

it('validates each step before moving on', function () {
    Livewire::test(SourcingWizard::class)
        ->call('next')->assertHasErrors(['form.title', 'form.description'])->assertSet('stepIndex', 0)
        ->set('form.title', 'Bags')->set('form.description', 'A bag with a long enough description.')
        ->call('next')->assertHasNoErrors()->assertSet('stepIndex', 1);
});

it('lets a guest submit a full request with private reference files', function () {
    Notification::fake();
    Storage::fake('local');

    $c = fillWizard(Livewire::test(SourcingWizard::class))
        ->set('uploads', [UploadedFile::fake()->image('sample.jpg'), UploadedFile::fake()->createWithContent('spec.pdf', "%PDF-1.4\n")])
        ->set('form.target_price', '1.25')
        ->set('consent', true);
    foreach (range(1, 4) as $i) {
        $c->call('next');
    }
    $c->call('submit')->assertHasNoErrors();

    $r = SourcingRequest::sole();
    expect($r->user_id)->toBeNull()
        ->and($r->status)->toBe(SourcingRequestStatus::Submitted)
        ->and($r->reference)->toMatch('/^CS-\d{4}-00001$/')
        ->and($r->target_price_cents)->toBe(125)
        ->and($r->files)->toHaveCount(2)
        ->and($r->events()->where('type', 'submitted')->exists())->toBeTrue()
        ->and($r->tasks()->count())->toBe(1);
    Storage::disk('local')->assertExists($r->files->first()->path);
    $c->assertSet('submittedReference', $r->reference);

    Notification::assertSentOnDemand(SourcingRequestReceived::class, fn ($n, $ch, $notifiable) => $notifiable->routes['mail'] === 'mark@webshop.nl');
    Notification::assertSentOnDemand(NewSourcingRequest::class);
});

it('requires consent and blocks the honeypot', function () {
    Notification::fake();
    $c = fillWizard(Livewire::test(SourcingWizard::class));
    foreach (range(1, 4) as $i) {
        $c->call('next');
    }
    $c->call('submit')->assertHasErrors('consent');
    $c->set('consent', true)->set('website', 'spam')->call('submit')->assertHasErrors('website');
    expect(SourcingRequest::count())->toBe(0);
});

it('links a logged-in buyer and forces the account email', function () {
    Notification::fake();
    $buyer = buyerUser();
    $this->actingAs($buyer);

    $c = fillWizard(Livewire::test(SourcingWizard::class), ['contact_email' => 'someone-else@example.com'])->set('consent', true);
    foreach (range(1, 4) as $i) {
        $c->call('next');
    }
    $r = SourcingRequest::query();
    $c->call('submit')->assertRedirect(route('account.requests.show', SourcingRequest::sole()->reference));

    $r = SourcingRequest::sole();
    expect($r->user_id)->toBe($buyer->id)->and($r->contact_email)->toBe($buyer->email);
});

it('adds a published preferred supplier as the first match, but never an unpublished one', function () {
    Notification::fake();
    $published = publishedSupplier();
    $hidden = makeSupplier(['name' => 'Hidden']);

    $this->get('/en/sourcing-request?supplier='.$published->slug)->assertSee($published->name);

    $r = makeRequest(['preferred_supplier_id' => $published->id]);
    expect($r->matches()->sole()->supplier_id)->toBe($published->id)->and($r->matches()->sole()->status)->toBe(MatchStatus::Suggested);

    $r2 = makeRequest(['preferred_supplier_id' => $hidden->id]);
    expect($r2->preferred_supplier_id)->toBeNull()->and($r2->matches()->count())->toBe(0);
});

it('rejects disguised files', function () {
    Notification::fake();
    Storage::fake('local');

    expect(fn () => app(SubmitSourcingRequest::class)->handle([
        'contact_name' => 'X', 'contact_email' => 'x@example.nl', 'title' => 'T', 'description' => 'Long enough description.', 'customization' => 'none',
        'quantity' => 1, 'currency' => 'EUR', 'destination_country' => 'NL',
    ], [UploadedFile::fake()->createWithContent('photo.jpg', '<?php system($_GET["c"]);')], null, 'en'))
        ->toThrow(ValidationException::class);

    expect(SourcingRequest::count())->toBe(0); // whole submission rolled back
});

it('rate limits submissions per IP', function () {
    Notification::fake();
    RateLimiter::clear('sourcing-request:127.0.0.1');
    foreach (range(1, 5) as $i) {
        RateLimiter::hit('sourcing-request:127.0.0.1', 3600);
    }
    $c = fillWizard(Livewire::test(SourcingWizard::class))->set('consent', true);
    foreach (range(1, 4) as $i) {
        $c->call('next');
    }
    $c->call('submit')->assertHasErrors('form.contact_email');
    expect(SourcingRequest::count())->toBe(0);
});

it('generates sequential references', function () {
    expect(makeRequest()->reference)->toEndWith('00001')->and(makeRequest()->reference)->toEndWith('00002');
});
