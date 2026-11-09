<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Models\Concerns\HasTranslationRows;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property ?int $parent_id
 * @property string $path
 * @property int $depth
 * @property PublicationStatus $status
 * @property bool $is_featured
 * @property ?string $icon
 * @property ?string $image_path
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CategoryTranslation> $translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $children
 * @property-read ?Category $parent
 */
class Category extends Model
{
    use HasTranslationRows, LogsActivity;

    protected $fillable = ['parent_id', 'status', 'is_featured', 'icon', 'image_path', 'sort_order'];

    protected function casts(): array
    {
        return [
            'status' => PublicationStatus::class,
            'is_featured' => 'boolean',
            'depth' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<CategoryTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    /** @return BelongsToMany<Supplier, $this> */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)->withPivot('is_primary');
    }

    /** @return BelongsTo<Category, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Path that this category's own children carry, e.g. "/1/5/".
     */
    public function childPath(): string
    {
        return $this->path.$this->id.'/';
    }

    public function scopeDescendantsOf(Builder $query, self $category): Builder
    {
        return $query->where('path', 'like', $category->childPath().'%');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Active);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * @return list<int>
     */
    public function ancestorIds(): array
    {
        return array_map('intval', array_values(array_filter(explode('/', $this->path))));
    }

    /**
     * A category is publicly visible only when it and every ancestor is active.
     */
    public function isPubliclyVisible(): bool
    {
        if ($this->status !== PublicationStatus::Active) {
            return false;
        }

        $ancestors = $this->ancestorIds();

        return $ancestors === []
            || self::whereIn('id', $ancestors)->where('status', '!=', PublicationStatus::Active)->doesntExist();
    }

    /**
     * @return Collection<int, self>
     */
    public function ancestors(): Collection
    {
        $ids = $this->ancestorIds();

        return $ids === []
            ? collect()
            : self::withCurrentTranslation()->whereIn('id', $ids)->orderBy('depth')->get();
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function name(?string $locale = null): string
    {
        return (string) $this->translated('name', $locale);
    }

    public function slug(?string $locale = null): ?string
    {
        return $this->translations->firstWhere('locale', $locale ?? app()->getLocale())?->slug;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['parent_id', 'status', 'is_featured', 'icon', 'image_path', 'sort_order'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
