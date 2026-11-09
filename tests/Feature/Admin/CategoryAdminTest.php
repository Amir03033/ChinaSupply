<?php

use App\Enums\StaffRole;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

it('lets an administrator browse one tree level at a time', function () {
    $root = makeCategory('Root NL', 'Root EN');
    $child = makeCategory('Kind NL', null, $root);

    $this->actingAs(staffUser(StaffRole::Administrator));

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords([$root])
        ->assertCanNotSeeTableRecords([$child]);

    Livewire::test(ListCategories::class, ['parent' => $root->id])
        ->assertCanSeeTableRecords([$child])
        ->assertCanNotSeeTableRecords([$root]);
});

it('searches across all levels', function () {
    $root = makeCategory('Wonen');
    $child = makeCategory('Keukengerei', null, $root);

    $this->actingAs(staffUser(StaffRole::Administrator));

    Livewire::test(ListCategories::class)->searchTable('Keuken')->assertCanSeeTableRecords([$child]);
});

it('creates a category with translations from the admin form', function () {
    $root = makeCategory('Root');
    $this->actingAs(staffUser(StaffRole::Administrator));

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'parent_id' => $root->id,
            'status' => 'active',
            'translations' => [
                'nl' => ['name' => 'Nieuwe categorie'],
                'en' => ['name' => 'New category', 'slug' => 'custom-slug'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $category = Category::with('translations')->latest('id')->first();
    expect($category->parent_id)->toBe($root->id)
        ->and($category->slug('nl'))->toBe('nieuwe-categorie')
        ->and($category->slug('en'))->toBe('custom-slug')
        ->and($category->depth)->toBe(1);
});

it('shows an error instead of saving a cyclic move', function () {
    $root = makeCategory('Root');
    $child = makeCategory('Child', null, $root);
    $this->actingAs(staffUser(StaffRole::Administrator));

    // The parent select excludes descendants; this asserts the server-side guard too.
    Livewire::test(EditCategory::class, ['record' => $root->id])
        ->fillForm(['parent_id' => $child->id])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);

    expect($root->fresh()->parent_id)->toBeNull();
});

it('records category changes in the audit log', function () {
    $admin = staffUser(StaffRole::Administrator);
    $this->actingAs($admin);
    $category = makeCategory('Audit');

    expect(Activity::where('subject_type', Category::class)
        ->where('subject_id', $category->id)->where('causer_id', $admin->id)->exists())->toBeTrue();
});

it('does not let a sourcing manager create or edit categories', function () {
    $category = makeCategory('Root');
    $this->actingAs(staffUser(StaffRole::SourcingManager));

    Livewire::test(ListCategories::class)->assertOk();
    Livewire::test(CreateCategory::class)->assertForbidden();
    Livewire::test(EditCategory::class, ['record' => $category->id])->assertForbidden();
    expect(CategoryResource::canCreate())->toBeFalse();
});

it('forces staff without two-factor authentication to set it up first', function () {
    $this->actingAs(staffUser(StaffRole::Administrator))
        ->get('/admin')
        ->assertRedirectContains('multi-factor-authentication');
});
