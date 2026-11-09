<?php

use App\Actions\Verification\RecordVerificationCheck;
use App\Enums\StaffRole;
use App\Enums\VerificationCheckStatus;
use App\Enums\VerificationCheckType as T;
use App\Models\VerificationCheck;
use App\Services\VerificationLevelCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

it('creates one not-checked row per check type for every new supplier', function () {
    $supplier = makeSupplier();

    expect($supplier->verificationChecks()->count())->toBe(count(T::cases()))
        ->and($supplier->verificationChecks()->where('status', '!=', VerificationCheckStatus::NotChecked)->count())->toBe(0)
        ->and($supplier->verification_level)->toBe(0);
});

it('derives levels strictly from the underlying checks', function () {
    $supplier = makeSupplier();

    passChecks($supplier, [T::CompanyIdentity, T::Contact]);
    expect($supplier->verification_level)->toBe(0);

    passChecks($supplier, [T::WebsiteDomain]);
    expect($supplier->verification_level)->toBe(1);

    passChecks($supplier, [T::BusinessRegistration]);
    expect($supplier->verification_level)->toBe(1);

    passChecks($supplier, [T::BusinessAddress]);
    expect($supplier->verification_level)->toBe(2);

    passChecks($supplier, [T::FactoryExistence]);
    expect($supplier->verification_level)->toBe(3)->and($supplier->last_verified_at)->not->toBeNull();
});

it('never skips a level: higher checks without the lower ones count for nothing', function () {
    $supplier = passChecks(makeSupplier(), [T::BusinessRegistration, T::BusinessAddress, T::FactoryExistence]);

    expect($supplier->verification_level)->toBe(0);
});

it('drops the level once a required check expires and flags re-verification', function () {
    $supplier = passChecks(makeSupplier(), levelOneChecks());
    expect($supplier->verification_level)->toBe(1)->and($supplier->verification_review_required)->toBeFalse();

    VerificationCheck::where('supplier_id', $supplier->id)->where('type', T::Contact)->update(['expires_at' => now()->addDays(10)]);
    app(VerificationLevelCalculator::class)->refresh($supplier);
    expect($supplier->verification_level)->toBe(1)->and($supplier->verification_review_required)->toBeTrue();

    $this->travel(11)->days();
    $this->artisan('suppliers:refresh-verification')->assertSuccessful();

    $supplier->refresh();
    expect($supplier->verification_level)->toBe(0)
        ->and($supplier->verification_review_required)->toBeTrue()
        ->and($supplier->tasks()->open()->where('title', 'Re-verification due')->count())->toBe(1);

    // Idempotent: running again does not create duplicate tasks.
    $this->artisan('suppliers:refresh-verification')->assertSuccessful();
    expect($supplier->tasks()->open()->count())->toBe(1);
});

it('applies the default validity when a check passes', function () {
    $supplier = passChecks(makeSupplier(), [T::CompanyIdentity]);
    $check = $supplier->verificationChecks()->where('type', T::CompanyIdentity)->first();

    expect($check->expires_at->isSameDay(now()->addMonths(12)))->toBeTrue()
        ->and($check->reviewer_id)->not->toBeNull();
});

it('only lets verification staff record checks', function (StaffRole $role) {
    app(RecordVerificationCheck::class)->handle(makeSupplier(), T::Contact, ['status' => 'passed', 'method' => 'x'], staffUser($role));
})->with([StaffRole::SourcingManager])->throws(AuthorizationException::class);

it('blocks buyers and suppliers from recording checks', function () {
    app(RecordVerificationCheck::class)->handle(makeSupplier(), T::Contact, ['status' => 'passed', 'method' => 'x'], buyerUser());
})->throws(AuthorizationException::class);

it('requires a public method for passed checks and a reason for failed ones', function () {
    $s = makeSupplier();
    $vs = staffUser(StaffRole::VerificationSpecialist);

    expect(fn () => app(RecordVerificationCheck::class)->handle($s, T::Contact, ['status' => 'passed'], $vs))->toThrow(ValidationException::class);
    expect(fn () => app(RecordVerificationCheck::class)->handle($s, T::Contact, ['status' => 'failed'], $vs))->toThrow(ValidationException::class);
});

it('rejects evidence that belongs to another supplier', function () {
    $other = makeSupplier(['name' => 'Other']);
    $doc = $other->documents()->create(['type' => 'evidence', 'path' => 'x', 'original_name' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 1, 'sha256' => str_repeat('a', 64)]);

    app(RecordVerificationCheck::class)->handle(makeSupplier(), T::Contact, ['status' => 'passed', 'method' => 'x', 'evidence_document_id' => $doc->id], staffUser(StaffRole::VerificationSpecialist));
})->throws(ValidationException::class);

it('keeps verification independent from payments and promotion', function () {
    // The calculator may only read verification checks. Billing/sponsoring code
    // (Phase 4/5) must never be referenced from it.
    expect(VerificationLevelCalculator::class)->not->toUse([
        'App\Models\Payment', 'App\Models\Order', 'App\Models\SupplierSubscription', 'App\Models\SponsoredPlacement', 'App\Billing', 'App\Payments',
    ]);
});
