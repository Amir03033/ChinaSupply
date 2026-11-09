<?php

use App\Actions\Categories\DeleteCategory;
use App\Actions\Categories\SaveCategory;
use App\Models\Category;
use App\Models\CategoryTranslation;
use Database\Seeders\CategorySeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

it('creates a root category with translations, slugs and path', function () {
    $category = makeCategory('Verpakking & Drukwerk', 'Packaging & Printing');

    expect($category->path)->toBe('/')
        ->and($category->depth)->toBe(0)
        ->and($category->slug('nl'))->toBe('verpakking-drukwerk')
        ->and($category->slug('en'))->toBe('packaging-printing');
});

it('nests children with materialised path and depth', function () {
    $root = makeCategory('Root');
    $child = makeCategory('Kind', null, $root);
    $grandchild = makeCategory('Kleinkind', null, $child);

    expect($child->path)->toBe("/{$root->id}/")
        ->and($grandchild->path)->toBe("/{$root->id}/{$child->id}/")
        ->and($grandchild->depth)->toBe(2)
        ->and(Category::descendantsOf($root)->pluck('id')->sort()->values()->all())
        ->toBe(collect([$child->id, $grandchild->id])->sort()->values()->all());
});

it('keeps slugs unique per locale but allows the same slug in another locale', function () {
    $a = makeCategory('Tassen', 'Bags');
    $b = makeCategory('Tassen', 'Tassen');

    expect($a->slug('nl'))->toBe('tassen')
        ->and($b->slug('nl'))->toBe('tassen-2')
        ->and($b->slug('en'))->toBe('tassen');
});

it('enforces locale+slug uniqueness at the database level', function () {
    $a = makeCategory('Uniek');
    $b = makeCategory('Anders');

    CategoryTranslation::where('category_id', $b->id)->where('locale', 'nl')->update(['slug' => $a->slug('nl')]);
})->throws(UniqueConstraintViolationException::class);

it('rewrites descendant paths when a category is moved', function () {
    $a = makeCategory('A');
    $b = makeCategory('B');
    $child = makeCategory('Child', null, $a);
    $grandchild = makeCategory('Grandchild', null, $child);

    app(SaveCategory::class)->handle($child, ['parent_id' => $b->id], ['nl' => ['name' => 'Child']]);

    expect($child->fresh()->path)->toBe("/{$b->id}/")
        ->and($grandchild->fresh()->path)->toBe("/{$b->id}/{$child->id}/")
        ->and($grandchild->fresh()->depth)->toBe(2);

    app(SaveCategory::class)->handle($child->fresh(), ['parent_id' => null], ['nl' => ['name' => 'Child']]);

    expect($child->fresh()->depth)->toBe(0)
        ->and($grandchild->fresh()->path)->toBe("/{$child->id}/")
        ->and($grandchild->fresh()->depth)->toBe(1);
});

it('refuses to move a category under itself or a descendant', function () {
    $root = makeCategory('Root');
    $child = makeCategory('Child', null, $root);

    expect(fn () => app(SaveCategory::class)->handle($root, ['parent_id' => $child->id], ['nl' => ['name' => 'Root']]))
        ->toThrow(ValidationException::class);
    expect(fn () => app(SaveCategory::class)->handle($root, ['parent_id' => $root->id], ['nl' => ['name' => 'Root']]))
        ->toThrow(ValidationException::class);
});

it('requires a name in the default locale', function () {
    app(SaveCategory::class)->handle(null, [], ['en' => ['name' => 'Only English']]);
})->throws(ValidationException::class);

it('removes a translation when its name is cleared', function () {
    $category = makeCategory('Naam', 'Name');

    app(SaveCategory::class)->handle($category, [], ['nl' => ['name' => 'Naam'], 'en' => ['name' => '']]);

    expect($category->fresh()->translations()->pluck('locale')->all())->toBe(['nl']);
});

it('blocks deleting a category that has subcategories', function () {
    $root = makeCategory('Root');
    makeCategory('Child', null, $root);

    app(DeleteCategory::class)->handle($root);
})->throws(ValidationException::class);

it('deletes a leaf category with its translations', function () {
    $leaf = makeCategory('Blad', 'Leaf');
    app(DeleteCategory::class)->handle($leaf);

    expect(Category::count())->toBe(0)->and(CategoryTranslation::count())->toBe(0);
});

it('seeds the initial taxonomy once and never overwrites it', function () {
    $this->seed(CategorySeeder::class);
    $roots = Category::roots()->count();
    $total = Category::count();

    $this->seed(CategorySeeder::class);

    expect($roots)->toBe(20)
        ->and(Category::count())->toBe($total)
        ->and(CategoryTranslation::where('locale', 'nl')->count())->toBe($total)
        ->and(CategoryTranslation::where('locale', 'en')->count())->toBe($total);
});
