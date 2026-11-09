<?php

use App\Enums\StaffRole;
use App\Enums\VerificationCheckType as T;
use App\Filament\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Suppliers\RelationManagers\CertificationsRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\CommunicationsRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\TasksRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\VerificationChecksRelationManager;
use App\Models\VerificationCheck;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

it('renders every supplier relation manager with data', function (string $manager) {
    $supplier = publishedSupplier();
    $supplier->contacts()->create(['name' => 'Ms Li', 'position' => 'Sales']);
    $supplier->products()->create(['name' => 'Box', 'price_min_cents' => 120, 'price_max_cents' => 300, 'currency' => 'USD']);
    $supplier->communications()->create(['channel' => 'email', 'direction' => 'inbound', 'occurred_at' => now()]);
    $supplier->notes()->create(['body' => 'Note']);
    $supplier->tasks()->create(['title' => 'Call', 'due_at' => now()]);
    $supplier->certifications()->create(['name' => 'ISO 9001']);
    $supplier->documents()->create(['type' => 'business_license', 'path' => 'x', 'original_name' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 1000, 'sha256' => str_repeat('b', 64)]);

    $this->actingAs(staffUser(StaffRole::Administrator));

    Livewire::test($manager, ['ownerRecord' => $supplier, 'pageClass' => EditSupplier::class])->assertOk();
})->with([
    VerificationChecksRelationManager::class, CommunicationsRelationManager::class, TasksRelationManager::class,
    NotesRelationManager::class, ContactsRelationManager::class, DocumentsRelationManager::class,
    CertificationsRelationManager::class, ProductsRelationManager::class,
]);

it('records a verification result from the admin and recalculates the level', function () {
    $supplier = makeSupplier();
    passChecks($supplier, [T::CompanyIdentity, T::Contact]);
    $this->actingAs(staffUser(StaffRole::VerificationSpecialist));
    $check = VerificationCheck::where('supplier_id', $supplier->id)->where('type', T::WebsiteDomain)->first();

    Livewire::test(VerificationChecksRelationManager::class, ['ownerRecord' => $supplier, 'pageClass' => EditSupplier::class])
        ->callTableAction('record', $check, ['status' => 'passed', 'method' => 'Domain registered since 2012, matches company name.']);

    expect($supplier->fresh()->verification_level)->toBe(1);
});

it('makes verification read-only for a sourcing manager', function () {
    $supplier = makeSupplier();
    $this->actingAs(staffUser(StaffRole::SourcingManager));
    $check = $supplier->verificationChecks()->first();

    Livewire::test(VerificationChecksRelationManager::class, ['ownerRecord' => $supplier, 'pageClass' => EditSupplier::class])
        ->assertTableActionHidden('record', $check);
});
