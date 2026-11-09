<?php

use App\Enums\VerificationCheckType as T;
use App\Models\VerificationCheck;
use App\Services\VerificationLevelCalculator;

it('shows published, verified suppliers only', function () {
    $draft = makeSupplier(['name' => 'Draft Co']);
    $this->get('/en/suppliers/'.$draft->slug)->assertNotFound();

    $supplier = publishedSupplier();
    $this->get('/en/suppliers/'.$supplier->slug)->assertOk()->assertSee('Published Supplier Ltd');
    $this->get('/nl/leveranciers/'.$supplier->slug)->assertOk();
});

it('hides a published supplier whose level drops to zero', function () {
    $supplier = publishedSupplier();
    VerificationCheck::where('supplier_id', $supplier->id)->where('type', T::Contact)->update(['expires_at' => now()->subDay()]);
    app(VerificationLevelCalculator::class)->refresh($supplier);

    $this->get('/en/suppliers/'.$supplier->slug)->assertNotFound();
});

it('shows what was and was not checked, without private data', function () {
    $supplier = publishedSupplier([
        'address' => 'Secret Road 1', 'business_email' => 'private@factory.cn', 'registration_number' => '91330200MA2A0B1C2D',
    ]);
    $supplier->notes()->create(['body' => 'Internal: owner seemed evasive']);
    $supplier->verificationChecks()->where('type', T::Contact)->update(['internal_notes' => 'called mobile 138xxx']);

    $this->get('/en/suppliers/'.$supplier->slug)->assertOk()
        ->assertSee(__('verification.checks.company_identity.name', [], 'en'))
        ->assertSee('Checked in test')
        ->assertSee(__('suppliers.check_state.not_checked', [], 'en'))
        ->assertDontSee('Secret Road 1')
        ->assertDontSee('private@factory.cn')
        ->assertDontSee('91330200MA2A0B1C2D')
        ->assertDontSee('evasive')
        ->assertDontSee('138xxx');
});

it('shows contact details only when the supplier allowed it', function () {
    $supplier = publishedSupplier(['business_email' => 'sales@factory.cn']);
    $supplier->forceFill(['show_contact_publicly' => true])->save();

    $this->get('/en/suppliers/'.$supplier->slug)->assertSee('sales@factory.cn');
});

it('lists published suppliers on their category and parent category pages', function () {
    $root = makeCategory('Verpakking', 'Packaging');
    $child = makeCategory('Dozen', 'Boxes', $root);
    $supplier = publishedSupplier([], $child);
    makeSupplier(['name' => 'Unpublished Boxes'])->categories()->attach($child->id);

    $this->get('/en/categories/boxes')->assertSee($supplier->name)->assertDontSee('Unpublished Boxes');
    $this->get('/en/categories/packaging')->assertSee($supplier->name);
});

it('prefills the contact form when requesting an introduction', function () {
    $supplier = publishedSupplier();

    $this->get('/en/contact?topic=sourcing&supplier='.$supplier->slug)->assertSee('get in touch with Published Supplier Ltd');
    $this->get('/en/contact?topic=sourcing&supplier=does-not-exist')->assertDontSee('get in touch with');
});
