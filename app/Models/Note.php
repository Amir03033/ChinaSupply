<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Internal staff notes. Never shown to buyers or suppliers.
 *
 * @property int $id
 * @property string $body
 * @property bool $is_pinned
 * @property-read ?User $user
 */
class Note extends Model
{
    protected $fillable = ['body', 'is_pinned', 'user_id'];

    protected function casts(): array
    {
        return ['is_pinned' => 'boolean'];
    }

    /** @return MorphTo<Model, $this> */
    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
