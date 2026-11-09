<?php

use App\Enums\StaffRole;
use App\Enums\SupplierPipelineStatus;
use App\Filament\Resources\SourcingRequests\Pages\ViewSourcingRequest;
use App\Filament\Resources\SourcingRequests\RelationManagers\EventsRelationManager;
use App\Filament\Resources\SourcingRequests\RelationManagers\FilesRelationManager;
use App\Filament\Resources\SourcingRequests\RelationManagers\MatchesRelationManager;
use App\Filament\Resources\SourcingRequests\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\SourcingRequests\RelationManagers\NotesRelationManager;
use App\Services\SupplierMatcher;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

it('suggests suppliers by category fit, MOQ fit and verification', function () {
    $cat = makeCategory('Tassen', 'Bags');
    $good = publishedSupplier(['name' => 'Good Fit', 'moq_min' => 300, 'offers_oem' => true], $cat);
    $highMoq = publishedSupplier(['name' => 'High MOQ', 'moq_min' => 50000], $cat);
    $rejected = makeSupplier(['name' => 'Rejected']);
    $rejected->categories()->attach($cat->id);
    $rejected->forceFill(['pipeline_status' => SupplierPipelineStatus::Rejected])->save();
    publishedSupplier(['name' => 'Wrong Category']);

    $r = makeRequest(['category_id' => $cat->id, 'quantity' => 500, 'customization' => 'logo']);
    $names = app(SupplierMatcher::class)->suggest($r)->pluck('supplier.name')->all();

    expect($names)->toBe(['Good Fit', 'High MOQ']);

    $r->matches()->create(['supplier_id' => $good->id]);
    expect(app(SupplierMatcher::class)->suggest($r)->pluck('supplier.name')->all())->toBe(['High MOQ']);
});

it('renders the request admin pages and relation managers', function (string $manager) {
    $admin = staffUser(StaffRole::Administrator);
    $admin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $this->actingAs($admin);
    $r = makeRequest();
    $r->matches()->create(['supplier_id' => publishedSupplier()->id, 'unit_price_cents' => 199]);
    $r->messages()->create(['from_staff' => false, 'body' => 'Hello']);

    $this->get('/admin/sourcing-requests')->assertOk()->assertSee($r->reference);
    $this->get('/admin/sourcing-requests/'.$r->id)->assertOk();
    expect($r->messages()->whereNull('read_at')->count())->toBe(0); // opened by staff → read

    Livewire::test($manager, ['ownerRecord' => $r, 'pageClass' => ViewSourcingRequest::class])->assertOk();
})->with([MatchesRelationManager::class, MessagesRelationManager::class, FilesRelationManager::class, EventsRelationManager::class, NotesRelationManager::class]);

it('adds top suggestions from the matches tab', function () {
    $this->actingAs(staffUser(StaffRole::SourcingManager));
    $cat = makeCategory('Tassen', 'Bags');
    publishedSupplier(['name' => 'Bag Factory'], $cat);
    $r = makeRequest(['category_id' => $cat->id]);

    Livewire::test(MatchesRelationManager::class, ['ownerRecord' => $r, 'pageClass' => ViewSourcingRequest::class])->callTableAction('suggest');

    expect($r->matches()->count())->toBe(1);
});

it('hides sourcing requests from verification specialists', function () {
    $user = staffUser(StaffRole::VerificationSpecialist);
    $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

    $this->actingAs($user)->get('/admin/sourcing-requests')->assertForbidden();
});
