<?php

use App\Actions\Suppliers\TransitionPipelineStatus;
use App\Actions\Suppliers\UpdateSupplierProfile;
use App\Enums\StaffRole;
use App\Enums\SupplierPipelineStatus as S;
use App\Enums\VerificationCheckStatus;
use App\Enums\VerificationCheckType as T;
use App\Filament\Imports\SupplierImporter;
use App\Filament\Pages\SupplierPipeline;
use App\Models\OutreachTemplate;
use App\Models\Supplier;
use Database\Seeders\OutreachTemplateSeeder;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

it('resets dependent passed checks when the supplier changes identity fields', function () {
    $supplier = passChecks(makeSupplier(['registration_number' => '91330200MA2A0B1C2D']), [...levelOneChecks(), T::BusinessRegistration, T::BusinessAddress]);
    expect($supplier->verification_level)->toBe(2);

    $reset = app(UpdateSupplierProfile::class)->handle($supplier, ['registration_number' => '91330200MA2A0B1C9Z']);

    expect(collect($reset)->map->value->sort()->values()->all())->toBe(['business_registration', 'company_identity'])
        ->and($supplier->fresh()->verification_level)->toBe(0)
        ->and($supplier->verificationChecks()->where('type', T::BusinessAddress)->value('status'))->toBe(VerificationCheckStatus::Passed);
});

it('ignores non-editable fields in supplier profile updates', function () {
    $supplier = makeSupplier();

    app(UpdateSupplierProfile::class)->handle($supplier, [
        'name' => 'New name', 'verification_level' => 3, 'pipeline_status' => 'published', 'assigned_to' => 1, 'show_contact_publicly' => true,
    ]);

    $supplier->refresh();
    expect($supplier->name)->toBe('New name')
        ->and($supplier->verification_level)->toBe(0)
        ->and($supplier->pipeline_status)->toBe(S::Discovered)
        ->and($supplier->show_contact_publicly)->toBeFalse();
});

it('only publishes suppliers that are verified, categorised and described', function () {
    $admin = staffUser(StaffRole::Administrator);
    $supplier = makeSupplier();

    expect(fn () => app(TransitionPipelineStatus::class)->handle($supplier, S::Published, $admin))->toThrow(ValidationException::class);

    passChecks($supplier, levelOneChecks());
    expect(fn () => app(TransitionPipelineStatus::class)->handle($supplier, S::Published, $admin))->toThrow(ValidationException::class);

    $published = publishedSupplier();
    expect($published->pipeline_status)->toBe(S::Published)->and($published->published_at)->not->toBeNull();
});

it('does not let a sourcing manager publish', function () {
    $supplier = passChecks(makeSupplier(), levelOneChecks());

    app(TransitionPipelineStatus::class)->handle($supplier, S::Published, staffUser(StaffRole::SourcingManager));
})->throws(AuthorizationException::class);

it('requires a reason to reject', function () {
    app(TransitionPipelineStatus::class)->handle(makeSupplier(), S::Rejected, staffUser(StaffRole::SourcingManager));
})->throws(ValidationException::class);

it('moves suppliers on the pipeline board using the same rules', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $supplier = makeSupplier();
    $this->actingAs(staffUser(StaffRole::SourcingManager));

    Livewire::test(SupplierPipeline::class)->call('move', $supplier->id, 'contacted');
    expect($supplier->fresh()->pipeline_status)->toBe(S::Contacted);

    Livewire::test(SupplierPipeline::class)->call('move', $supplier->id, 'published');
    expect($supplier->fresh()->pipeline_status)->toBe(S::Contacted);
});

it('syncs last contact and next follow-up from communications and tasks', function () {
    $supplier = makeSupplier();
    $supplier->communications()->create(['channel' => 'email', 'direction' => 'outbound', 'occurred_at' => now()->subDay()]);
    $supplier->tasks()->create(['title' => 'Later', 'due_at' => now()->addDays(10)]);
    $soon = $supplier->tasks()->create(['title' => 'Soon', 'due_at' => now()->addDays(2)]);

    $supplier->refresh();
    expect($supplier->last_contacted_at)->not->toBeNull()
        ->and($supplier->next_follow_up_at->isSameDay(now()->addDays(2)))->toBeTrue();

    $soon->update(['completed_at' => now()]);
    expect($supplier->fresh()->next_follow_up_at->isSameDay(now()->addDays(10)))->toBeTrue();
});

it('renders outreach templates with placeholders', function () {
    $this->seed(OutreachTemplateSeeder::class);
    $supplier = makeSupplier(['name' => 'Acme Factory']);
    $contact = $supplier->contacts()->create(['name' => 'Ms Li']);

    $out = OutreachTemplate::where('name', 'First contact')->first()->render($supplier, $contact, staffUser(StaffRole::SourcingManager));

    expect($out['body'])->toContain('Dear Ms Li')->toContain('Acme Factory')->not->toContain('{company}');
});

it('imports suppliers from CSV rows and updates instead of duplicating', function () {
    $user = staffUser(StaffRole::SourcingManager);
    $category = makeCategory('Verzenddozen', 'Shipping boxes');
    $import = Import::create(['file_name' => 'x.csv', 'file_path' => 'x.csv', 'importer' => SupplierImporter::class, 'total_rows' => 2, 'user_id' => $user->id]);
    $columns = collect(SupplierImporter::getColumns())->mapWithKeys(fn ($c) => [$c->getName() => $c->getName()])->all();
    $importer = new SupplierImporter($import, $columns, []);

    $importer(['name' => 'Ningbo Boxes', 'website' => 'https://www.ningbo-boxes.cn', 'source' => 'alibaba', 'categories' => 'shipping-boxes', 'offers_oem' => 'yes']);
    $importer(['name' => 'Ningbo Boxes Co', 'website' => 'http://ningbo-boxes.cn/about', 'city' => 'Ningbo']);

    expect(Supplier::count())->toBe(1);
    $supplier = Supplier::first();
    expect($supplier->name)->toBe('Ningbo Boxes Co')
        ->and($supplier->city)->toBe('Ningbo')
        ->and($supplier->pipeline_status)->toBe(S::Discovered)
        ->and($supplier->categories()->pluck('categories.id')->all())->toBe([$category->id])
        ->and($supplier->assigned_to)->toBe($user->id)
        ->and($supplier->verificationChecks()->count())->toBe(count(T::cases()));
});

it('never imports status or verification level', function () {
    $user = staffUser(StaffRole::SourcingManager);
    $import = Import::create(['file_name' => 'x.csv', 'file_path' => 'x.csv', 'importer' => SupplierImporter::class, 'total_rows' => 1, 'user_id' => $user->id]);
    $importer = new SupplierImporter($import, ['name' => 'name', 'verification_level' => 'verification_level'], []);

    $importer(['name' => 'Sneaky Ltd', 'verification_level' => '3', 'pipeline_status' => 'published']);

    $s = Supplier::where('name', 'Sneaky Ltd')->first();
    expect($s->verification_level)->toBe(0)->and($s->pipeline_status)->toBe(S::Discovered);
});
