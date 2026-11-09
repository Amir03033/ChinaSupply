<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $page_id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property ?string $body
 * @property ?string $seo_title
 * @property ?string $seo_description
 * @property-read Page $page
 */
class PageTranslation extends Model
{
    use LogsActivity;

    protected $fillable = ['locale', 'title', 'slug', 'body', 'seo_title', 'seo_description'];

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['title', 'slug', 'seo_title', 'seo_description'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
