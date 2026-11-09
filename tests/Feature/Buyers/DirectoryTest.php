<?php

use App\Enums\StaffRole;
use App\Enums\VerificationCheckType as T;
use App\Models\Certification;
use Database\Seeders\CertificationSeeder;

it('lists only published, verified suppliers', function () {
    $published = publishedSupplier(['name' => 'Visible Factory']);
    makeSupplier(['name' => 'Hidden Factory']);

    $this->get('/en/suppliers')->assertOk()->assertSee('Visible Factory')->assertDontSee('Hidden Factory');
    $this->get('/nl/leveranciers')->assertOk();
});

it('filters by category including subcategories, type, MOQ, OEM and level', function () {
    $root = makeCategory('Verpakking', 'Packaging');
    $child = makeCategory('Dozen', 'Boxes', $root);
    $other = makeCategory('Textiel', 'Textiles');

    $a = publishedSupplier(['name' => 'Box Maker', 'company_type' => 'manufacturer', 'moq_min' => 300, 'offers_oem' => true], $child);
    $b = publishedSupplier(['name' => 'Cloth Trader', 'company_type' => 'trading_company', 'moq_min' => 5000], $other);
    $c = publishedSupplier(['name' => 'Unknown MOQ Boxes'], $child);
    passChecks($a, [T::BusinessRegistration, T::BusinessAddress]);

    $this->get('/en/suppliers?category=packaging')->assertSee('Box Maker')->assertSee('Unknown MOQ Boxes')->assertDontSee('Cloth Trader');
    $this->get('/en/suppliers?type=trading_company')->assertSee('Cloth Trader')->assertDontSee('Box Maker');
    $this->get('/en/suppliers?moq_max=500')->assertSee('Box Maker')->assertDontSee('Cloth Trader')->assertDontSee('Unknown MOQ Boxes');
    $this->get('/en/suppliers?oem=1')->assertSee('Box Maker')->assertDontSee('Cloth Trader');
    $this->get('/en/suppliers?level=2')->assertSee('Box Maker')->assertDontSee('Cloth Trader');
});

it('ignores invalid filter values', function () {
    publishedSupplier(['name' => 'Box Maker']);

    $this->get('/en/suppliers?moq_max=7&level=9&type=evil&sort=drop')->assertOk()->assertSee('Box Maker');
});

it('only filters on certificates that ChinaSupply verified', function () {
    $this->seed(CertificationSeeder::class);
    $iso = Certification::where('code', 'ISO9001')->first();
    $verified = publishedSupplier(['name' => 'Verified ISO']);
    $claimed = publishedSupplier(['name' => 'Claimed ISO']);
    $verified->certifications()->create(['name' => $iso->name, 'certification_id' => $iso->id])->forceFill(['status' => 'verified'])->save();
    $claimed->certifications()->create(['name' => $iso->name, 'certification_id' => $iso->id]);

    $this->get('/en/suppliers?cert=ISO9001')->assertSee('Verified ISO')->assertDontSee('Claimed ISO');
});

it('searches names, descriptions, products and category names', function () {
    $cat = makeCategory('LED-verlichting', 'LED lighting');
    $s = publishedSupplier(['name' => 'Bright Co'], $cat);
    $s->products()->create(['name' => 'Flexible strip light']);
    publishedSupplier(['name' => 'Other Co']);

    $this->get('/en/suppliers?q=Bright')->assertSee('Bright Co')->assertDontSee('Other Co');
    $this->get('/en/suppliers?q=strip')->assertSee('Bright Co')->assertDontSee('Other Co');
    $this->get('/en/suppliers?q=lighting')->assertSee('Bright Co');
    $this->get('/en/suppliers?q=%25')->assertDontSee('Bright Co');
});

it('noindexes filtered result pages but not the plain directory', function () {
    $this->get('/en/suppliers')->assertDontSee('noindex', false);
    $this->get('/en/suppliers?q=box')->assertSee('noindex', false);
});

it('compares up to three published suppliers', function () {
    $suppliers = collect(range(1, 4))->map(fn ($i) => publishedSupplier(['name' => "Factory $i"]));

    foreach ($suppliers->take(3) as $s) {
        $this->post(route('compare.toggle', $s))->assertRedirect();
    }
    $this->post(route('compare.toggle', $suppliers->last()))->assertSessionHas('directory_status');

    $this->get('/en/suppliers/compare')->assertOk()->assertSee('Factory 1')->assertSee('Factory 3')->assertDontSee('Factory 4');

    $this->post(route('compare.toggle', $suppliers->first()));
    $this->get('/en/suppliers/compare')->assertDontSee('Factory 1');
});

it('refuses to compare unpublished suppliers', function () {
    $this->post(route('compare.toggle', makeSupplier(['name' => 'Hidden'])))->assertNotFound();
});

it('lets buyers save suppliers and blocks others', function () {
    $s = publishedSupplier();
    $buyer = buyerUser();

    $this->post(route('favorites.toggle', $s))->assertRedirect('/login');
    $this->actingAs($buyer)->post(route('favorites.toggle', $s))->assertRedirect();
    expect($buyer->favoriteSuppliers()->count())->toBe(1);
    $this->actingAs($buyer)->get('/account/favorites')->assertOk()->assertSee($s->name);
    $this->actingAs($buyer)->post(route('favorites.toggle', $s));
    expect($buyer->favoriteSuppliers()->count())->toBe(0);

    $this->actingAs(staffUser(StaffRole::Administrator))->post(route('favorites.toggle', $s))->assertForbidden();
});
