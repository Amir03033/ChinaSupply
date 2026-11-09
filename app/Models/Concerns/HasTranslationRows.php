<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Translations stored as one row per locale in a "<model>_translations" table.
 * Chosen over JSON columns because slugs must be unique per locale, which a
 * unique(locale, slug) index enforces at the database level for any number of
 * future languages.
 *
 * The using model defines translations(): HasMany.
 */
trait HasTranslationRows
{
    /** @return HasMany<covariant Model, $this> */
    abstract public function translations(): HasMany;

    public function translation(?string $locale = null): ?Model
    {
        $locale ??= app()->getLocale();
        $rows = $this->relationLoaded('translations') ? $this->translations : $this->translations()->get();

        return $rows->firstWhere('locale', $locale)
            ?? $rows->firstWhere('locale', config('app.fallback_locale'))
            ?? $rows->first();
    }

    public function translated(string $attribute, ?string $locale = null): ?string
    {
        return $this->translation($locale)?->{$attribute};
    }

    /**
     * Only translations in the current locale (plus fallback) are eager loaded.
     */
    public function scopeWithCurrentTranslation(Builder $query): Builder
    {
        return $query->with(['translations' => fn ($q) => $q->whereIn('locale', array_unique([
            app()->getLocale(), config('app.fallback_locale'),
        ]))]);
    }

    public function scopeWhereSlug(Builder $query, string $slug, ?string $locale = null): Builder
    {
        return $query->whereHas('translations', fn ($q) => $q
            ->where('locale', $locale ?? app()->getLocale())
            ->where('slug', $slug));
    }
}
