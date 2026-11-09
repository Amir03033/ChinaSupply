<?php

use App\Actions\Suppliers\StoreSupplierDocument;
use App\Enums\DocumentType;
use App\Enums\StaffRole;
use App\Enums\SupplierPipelineStatus as S;
use App\Enums\UserType;
use App\Enums\VerificationCheckType as T;
use App\Livewire\SupplierPortal;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\SupplierSubmittedForReview;
use Database\Seeders\PageSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

function registerSupplier(array $override = []): TestResponse
{
    return test()->post('/en/supplier-registration', $override + [
        'company_name' => 'Shenzhen Bright LED Co.',
        'country' => 'CN',
        'name' => 'Wei Zhang',
        'email' => 'wei@bright-led.cn',
        'password' => 'a-long-password-123',
        'password_confirmation' => 'a-long-password-123',
        'agreement' => '1',
    ]);
}

function supplierUser(): User
{
    registerSupplier();
    $user = User::where('email', 'wei@bright-led.cn')->first();
    $user->markEmailAsVerified();
    auth()->logout();

    return $user;
}

it('renders the supplier registration page in both locales', function () {
    $this->seed(PageSeeder::class);
    $this->get('/en/supplier-registration')->assertOk();
    $this->get('/nl/leverancier-aanmelden')->assertOk();
});

it('registers a supplier account linked to a new self-registered supplier', function () {
    registerSupplier()->assertRedirect(route('supplier.portal'));

    $user = User::where('email', 'wei@bright-led.cn')->sole();
    $supplier = $user->supplier();

    expect($user->type)->toBe(UserType::Supplier)
        ->and($supplier->name)->toBe('Shenzhen Bright LED Co.')
        ->and($supplier->pipeline_status)->toBe(S::Applied)
        ->and($supplier->source->value)->toBe('self_registration')
        ->and($supplier->contacts()->first()->email)->toBe('wei@bright-led.cn');
});

it('rejects registration without agreement or with a honeypot', function () {
    registerSupplier(['agreement' => null])->assertSessionHasErrors('agreement');
    registerSupplier(['website_url' => 'x'])->assertSessionHasErrors('website_url');
    expect(Supplier::count())->toBe(0);
});

it('keeps buyer, supplier and staff areas separate', function () {
    $supplier = supplierUser();
    $buyer = buyerUser();

    $this->actingAs($supplier)->get('/account')->assertRedirect(route('supplier.portal'));
    $this->actingAs($supplier)->get('/supplier-portal')->assertOk();
    $this->actingAs($supplier)->get('/admin')->assertForbidden();
    $this->actingAs($buyer)->get('/supplier-portal')->assertRedirect(route('account'));
});

it('autosaves whitelisted fields and ignores attempts to set verification fields', function () {
    $user = supplierUser();
    $this->actingAs($user);

    Livewire::test(SupplierPortal::class)
        ->set('form.legal_name', 'Shenzhen Bright LED Technology Co., Ltd.')
        ->set('form.city', 'Shenzhen')
        ->set('description', 'We manufacture LED strips.')
        ->set('form.verification_level', 3)
        ->set('form.pipeline_status', 'published')
        ->assertHasNoErrors();

    $supplier = $user->supplier();
    expect($supplier->legal_name)->toBe('Shenzhen Bright LED Technology Co., Ltd.')
        ->and($supplier->city)->toBe('Shenzhen')
        ->and($supplier->description('en'))->toBe('We manufacture LED strips.')
        ->and($supplier->verification_level)->toBe(0)
        ->and($supplier->pipeline_status)->toBe(S::Applied);
});

it('validates autosaved fields', function () {
    $this->actingAs(supplierUser());

    Livewire::test(SupplierPortal::class)->set('form.website', 'not a url')->assertHasErrors('form.website');
});

it('limits categories and only accepts active ones', function () {
    $this->actingAs($user = supplierUser());
    $root = makeCategory('Root');
    $cats = collect(range(1, 11))->map(fn ($i) => makeCategory("Cat $i", null, $root));

    $component = Livewire::test(SupplierPortal::class);
    foreach ($cats->take(10) as $c) {
        $component->call('toggleCategory', $c->id);
    }
    $component->call('toggleCategory', $cats->last()->id)->assertHasErrors('categoryIds');

    expect($user->supplier()->categories()->count())->toBe(10);
});

it('stores uploads privately and detects the real file type', function () {
    Storage::fake('local');
    $this->actingAs($user = supplierUser());

    Livewire::test(SupplierPortal::class)
        ->set('documentType', 'business_license')
        ->set('documentUpload', UploadedFile::fake()->createWithContent('licence.pdf', "%PDF-1.4\n%test\n"))
        ->call('uploadDocument')
        ->assertHasNoErrors();

    $doc = $user->supplier()->documents()->sole();
    expect($doc->mime)->toBe('application/pdf')->and($doc->disk)->toBe('local')
        ->and($doc->path)->not->toContain('licence');
    Storage::disk('local')->assertExists($doc->path);

    // A PHP script renamed to .pdf is refused (MIME detected from content).
    Livewire::test(SupplierPortal::class)
        ->set('documentType', 'certificate')
        ->set('documentUpload', UploadedFile::fake()->createWithContent('cert.pdf', '<?php echo "x";'))
        ->call('uploadDocument')
        ->assertHasErrors('documentUpload');
});

it('protects document downloads', function () {
    Storage::fake('local');
    $owner = supplierUser();
    $supplier = $owner->supplier();
    $doc = app(StoreSupplierDocument::class)->handle($supplier, UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\n"), DocumentType::BusinessLicense, $owner);
    $evidence = app(StoreSupplierDocument::class)->handle($supplier, UploadedFile::fake()->createWithContent('e.pdf', "%PDF-1.4\nevidence"), DocumentType::Evidence, null);

    $this->get(route('documents.download', $doc))->assertRedirect('/login');
    $this->actingAs($owner)->get(route('documents.download', $doc))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs($owner)->get(route('documents.download', $evidence))->assertForbidden();
    $this->actingAs(buyerUser())->get(route('documents.download', $doc))->assertForbidden();
    $this->actingAs(staffUser(StaffRole::SourcingManager))->get(route('documents.download', $evidence))->assertOk();

    $otherSupplierUser = User::factory()->create(['type' => UserType::Supplier]);
    makeSupplier(['name' => 'Other'])->users()->attach($otherSupplierUser->id);
    $this->actingAs($otherSupplierUser)->get(route('documents.download', $doc))->assertForbidden();
});

it('cannot touch another supplier\'s products', function () {
    $this->actingAs(supplierUser());
    $other = makeSupplier(['name' => 'Other']);
    $product = $other->products()->create(['name' => 'Foreign product']);

    Livewire::test(SupplierPortal::class)->call('deleteProduct', $product->id)->assertNotFound();
    expect($product->fresh())->not->toBeNull();
});

it('submits a complete profile for review once and notifies staff', function () {
    Notification::fake();
    Storage::fake('local');
    $this->actingAs($user = supplierUser());
    $category = makeCategory('LED', 'LED');

    $component = Livewire::test(SupplierPortal::class)->call('submit')->assertHasErrors('submit');
    expect($user->supplier()->pipeline_status)->toBe(S::Applied);

    $component
        ->set('form.legal_name', 'Bright LED Ltd')->set('form.registration_number', '91440300MA5F1234XY')
        ->set('form.company_type', 'manufacturer')->set('form.province', 'Guangdong')->set('form.city', 'Shenzhen')
        ->set('form.address', 'Bao\'an District')->set('form.business_email', 'sales@bright-led.cn')
        ->set('description', 'LED strips since 2010.')
        ->call('toggleCategory', $category->id)
        ->set('documentType', 'business_license')
        ->set('documentUpload', UploadedFile::fake()->createWithContent('licence.pdf', "%PDF-1.4\n"))
        ->call('uploadDocument')
        ->call('submit')
        ->assertHasNoErrors();

    $supplier = $user->supplier();
    expect($supplier->pipeline_status)->toBe(S::VerificationPending)
        ->and($supplier->onboarding_submitted_at)->not->toBeNull()
        ->and($supplier->tasks()->where('title', 'Review supplier application')->count())->toBe(1);
    Notification::assertSentOnDemandTimes(SupplierSubmittedForReview::class, 1);

    // Resubmitting does not create another task or mail.
    $component->call('submit');
    expect($supplier->tasks()->count())->toBe(1);
    Notification::assertSentOnDemandTimes(SupplierSubmittedForReview::class, 1);
});

it('warns the supplier when edits reset verified checks', function () {
    $this->actingAs($user = supplierUser());
    passChecks($user->supplier(), levelOneChecks());

    Livewire::test(SupplierPortal::class)
        ->set('form.website', 'https://new-domain.cn')
        ->assertSet('resetChecks', [T::WebsiteDomain->value]);

    expect($user->supplier()->verification_level)->toBe(0);
});
