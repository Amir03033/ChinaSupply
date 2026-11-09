<?php

use App\Enums\PublicationStatus;
use Database\Seeders\PageSeeder;

it('redirects the root to the preferred locale', function () {
    $this->get('/', ['Accept-Language' => 'en-GB,en;q=0.9'])->assertRedirect(url('/en'));
    $this->get('/', ['Accept-Language' => 'de-DE'])->assertRedirect(url('/nl'));
});

it('renders the core pages in both locales', function (string $url) {
    $this->seed(PageSeeder::class);
    $this->get($url)->assertOk();
})->with([
    '/nl', '/en', '/nl/categorieen', '/en/categories', '/nl/hoe-het-werkt', '/en/how-it-works',
    '/nl/verificatie', '/en/verification', '/nl/voor-leveranciers', '/en/for-suppliers',
    '/nl/over-ons', '/en/about', '/nl/contact', '/en/contact',
]);

it('sets the html lang attribute and hreflang alternates', function () {
    $this->get('/en/how-it-works')
        ->assertSee('<html lang="en"', false)
        ->assertSee('hreflang="nl" href="'.url('/nl/hoe-het-werkt').'"', false)
        ->assertSee('hreflang="x-default"', false);
});

it('shows featured active categories on the homepage only', function () {
    makeCategory('Uitgelicht', 'Featured', null, ['is_featured' => true]);
    makeCategory('Concept uitgelicht', null, null, ['is_featured' => true, 'status' => PublicationStatus::Draft->value]);

    $this->get('/nl')->assertSee('Uitgelicht')->assertDontSee('Concept uitgelicht');
});

it('resolves category slugs per locale with correct alternates', function () {
    $category = makeCategory('Verpakking', 'Packaging');

    $this->get('/nl/categorieen/verpakking')->assertOk()->assertSee('Verpakking')
        ->assertSee('hreflang="en" href="'.url('/en/categories/packaging').'"', false);
    $this->get('/en/categories/packaging')->assertOk();
    // A Dutch slug does not resolve under the English prefix.
    $this->get('/en/categories/verpakking')->assertNotFound();
});

it('hides draft and inactive categories and children of hidden parents', function () {
    $hidden = makeCategory('Verborgen', null, null, ['status' => PublicationStatus::Inactive->value]);
    makeCategory('Kind van verborgen', null, $hidden);
    makeCategory('Concept', null, null, ['status' => PublicationStatus::Draft->value]);

    $this->get('/nl/categorieen/verborgen')->assertNotFound();
    $this->get('/nl/categorieen/kind-van-verborgen')->assertNotFound();
    $this->get('/nl/categorieen/concept')->assertNotFound();
    $this->get('/nl/categorieen')->assertDontSee('Verborgen')->assertDontSee('Concept');
});

it('searches categories in the current locale and noindexes results', function () {
    $root = makeCategory('Verpakking', 'Packaging');
    makeCategory('Verzenddozen', 'Shipping boxes', $root);

    $this->get('/nl/categorieen?q=dozen')->assertOk()->assertSee('Verzenddozen')
        ->assertSee('noindex', false);
    $this->get('/nl/categorieen?q=bestaatniet')->assertOk()->assertSee(__('categories.no_results_title', [], 'nl'));
});

it('treats LIKE wildcards in search as literal text', function () {
    makeCategory('Tassen');

    $this->get('/nl/categorieen?q=%25')->assertOk()->assertDontSee('Tassen');
});

it('shows an honest empty state instead of fake suppliers', function () {
    makeCategory('Textiel', 'Textiles');

    $this->get('/nl/categorieen/textiel')->assertSee(__('categories.no_suppliers_title', [], 'nl'));
});
