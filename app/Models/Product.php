<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $supplier_id
 * @property ?int $category_id
 * @property string $name
 * @property string $slug
 * @property ?string $description
 * @property ?int $moq
 * @property ?string $moq_unit
 * @property ?int $price_min_cents
 * @property ?int $price_max_cents
 * @property string $currency
 * @property ?int $lead_time_days
 * @property bool $customizable
 * @property ?string $image_path
 * @property bool $is_published
 * @property-read ?Category $category
 */
class Product extends Model
{
    use LogsActivity;

    protected $fillable = ['category_id', 'name', 'description', 'moq', 'moq_unit', 'price_min_cents', 'price_max_cents', 'currency', 'lead_time_days', 'customizable', 'image_path', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return ['customizable' => 'boolean', 'is_published' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if ($product->isDirty('name') || ! $product->slug) {
                $base = Str::slug($product->name) ?: 'product';
                $slug = $base;
                $i = 2;
                while (static::where('supplier_id', $product->supplier_id)->where('slug', $slug)
                    ->when($product->exists, fn ($q) => $q->whereKeyNot($product->id))->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $product->slug = $slug;
            }
        });
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
