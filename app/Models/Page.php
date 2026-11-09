<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Models\Concerns\HasTranslationRows;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $key
 * @property PublicationStatus $status
 * @property bool $requires_legal_review
 * @property bool $show_in_footer
 * @property int $sort_order
 * @property-read Collection<int, PageTranslation> $translations
 */
class Page extends Model
{
    use HasTranslationRows, LogsActivity;

    protected $fillable = ['key', 'status', 'requires_legal_review', 'show_in_footer', 'sort_order'];

    protected function casts(): array
    {
        return [
            'status' => PublicationStatus::class,
            'requires_legal_review' => 'boolean',
            'show_in_footer' => 'boolean',
        ];
    }

    /** @return HasMany<PageTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PublicationStatus::Active);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
