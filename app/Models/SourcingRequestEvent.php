<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Timeline entry. Buyer-visible entries are rendered from translation keys
 * ("requests.events.<type>") so the buyer reads them in their own language.
 *
 * @property int $id
 * @property string $type
 * @property ?array<string, mixed> $data
 * @property bool $visible_to_buyer
 * @property ?int $user_id
 * @property Carbon $created_at
 * @property-read ?User $user
 */
class SourcingRequestEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['type', 'data', 'visible_to_buyer', 'user_id'];

    protected function casts(): array
    {
        return ['data' => 'array', 'visible_to_buyer' => 'boolean', 'created_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function describe(?string $locale = null): string
    {
        $data = collect($this->data ?? [])->map(function ($v, $k) use ($locale) {
            if ($k === 'status' && is_string($v)) {
                return __('requests.buyer_status.'.$v, [], $locale);
            }

            return is_scalar($v) ? (string) $v : '';
        })->all();

        return __('requests.events.'.$this->type, $data, $locale);
    }
}
