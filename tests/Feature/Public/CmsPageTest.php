<?php

use App\Actions\Pages\SavePage;
use App\Enums\PublicationStatus;
use App\Models\Page;
use Database\Seeders\PageSeeder;

it('shows seeded legal pages with a draft notice and links them in the footer', function () {
    $this->seed(PageSeeder::class);

    $this->get('/nl/info/privacyverklaring')->assertOk()
        ->assertSee(__('pages.cms.legal_review_notice', [], 'nl'));
    $this->get('/en/info/privacy-policy')->assertOk();
    $this->get('/nl')->assertSee('/nl/info/algemene-voorwaarden', false);
});

it('returns 404 for draft pages', function () {
    app(SavePage::class)->handle(null, ['key' => 'secret', 'status' => PublicationStatus::Draft->value], ['nl' => ['title' => 'Geheim']]);

    $this->get('/nl/info/geheim')->assertNotFound();
});

it('strips scripts and unsafe attributes from page HTML', function () {
    app(SavePage::class)->handle(null, ['key' => 'x', 'status' => PublicationStatus::Active->value], ['nl' => [
        'title' => 'Test',
        'body' => '<p onclick="alert(1)">Hallo</p><script>alert(2)</script><a href="javascript:alert(3)">x</a>',
    ]]);

    $html = Page::where('key', 'x')->first()->translation('nl')->body;

    expect($html)->not->toContain('script')->not->toContain('onclick')->not->toContain('javascript:')
        ->toContain('<p>Hallo</p>');
});
