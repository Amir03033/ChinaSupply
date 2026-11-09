<?php

namespace App\Actions\Categories;

use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Creates or updates a category with its per-locale translations.
 *
 * Keeps the materialised path/depth of the category and all of its
 * descendants consistent, rejects moves that would create a cycle and
 * generates locale-unique slugs.
 */
class SaveCategory
{
    /**
     * @param  array{parent_id?: int|null, status?: string, is_featured?: bool, icon?: string|null, image_path?: string|null, sort_order?: int|null}  $attributes
     * @param  array<string, array{name?: string|null, slug?: string|null, description?: string|null, seo_title?: string|null, seo_description?: string|null}>  $translations  keyed by locale
     */
    public function handle(?Category $category, array $attributes, array $translations): Category
    {
        $category ??= new Category;
        $parent = isset($attributes['parent_id'])
            ? Category::findOrFail($attributes['parent_id'])
            : null;

        $this->guardAgainstCycle($category, $parent);
        $this->guardRequiredTranslation($translations);

        return DB::transaction(function () use ($category, $attributes, $translations, $parent) {
            $oldChildPath = $category->exists ? $category->childPath() : null;

            $category->fill(collect($attributes)->except('parent_id')->all());
            $category->parent_id = $parent?->id;
            $category->path = $parent ? $parent->childPath() : '/';
            $category->depth = $parent ? $parent->depth + 1 : 0;

            if (! $category->exists && ! isset($attributes['sort_order'])) {
                $category->sort_order = (int) Category::where('parent_id', $parent?->id)->max('sort_order') + 1;
            }

            $category->save();

            if ($oldChildPath !== null && $oldChildPath !== $category->childPath()) {
                $this->rewriteDescendantPaths($oldChildPath, $category->childPath());
            }

            foreach ($translations as $locale => $data) {
                $this->saveTranslation($category, $locale, $data);
            }

            return $category->load('translations');
        });
    }

    private function guardAgainstCycle(Category $category, ?Category $parent): void
    {
        if (! $category->exists || $parent === null) {
            return;
        }

        if ($parent->id === $category->id || str_starts_with($parent->path, $category->childPath())) {
            throw ValidationException::withMessages([
                'parent_id' => __('admin.categories.errors.cycle'),
            ]);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    private function guardRequiredTranslation(array $translations): void
    {
        $default = config('chinasupply.default_locale');

        if (blank($translations[$default]['name'] ?? null)) {
            throw ValidationException::withMessages([
                "translations.{$default}.name" => __('admin.categories.errors.default_name_required'),
            ]);
        }
    }

    private function rewriteDescendantPaths(string $oldPrefix, string $newPrefix): void
    {
        Category::where('path', 'like', $oldPrefix.'%')->get()->each(function (Category $descendant) use ($oldPrefix, $newPrefix) {
            $descendant->path = $newPrefix.substr($descendant->path, strlen($oldPrefix));
            $descendant->depth = count(array_filter(explode('/', $descendant->path)));
            $descendant->saveQuietly();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveTranslation(Category $category, string $locale, array $data): void
    {
        $existing = $category->translations()->where('locale', $locale)->first();

        if (blank($data['name'] ?? null)) {
            // Empty name for a non-default locale = no translation; remove a stale one.
            $existing?->delete();

            return;
        }

        $slug = Str::slug(filled($data['slug'] ?? null) ? $data['slug'] : $data['name']);
        $slug = $this->uniqueSlug($slug !== '' ? $slug : 'category', $locale, $existing?->id);

        $translation = $existing ?? new CategoryTranslation(['locale' => $locale]);
        $translation->fill([
            'name' => trim($data['name']),
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
        ]);
        $category->translations()->save($translation);
    }

    private function uniqueSlug(string $slug, string $locale, ?int $ignoreId): string
    {
        $candidate = $slug;
        $i = 2;

        while (CategoryTranslation::where('locale', $locale)
            ->where('slug', $candidate)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $candidate = $slug.'-'.$i++;
        }

        return $candidate;
    }
}
