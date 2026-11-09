<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sourcing_request_id
 * @property ?int $user_id
 * @property bool $from_staff
 * @property string $body
 * @property ?Carbon $read_at
 * @property Carbon $created_at
 * @property-read ?User $user
 */
class SourcingRequestMessage extends Model
{
    protected $fillable = ['user_id', 'from_staff', 'body'];

    protected function casts(): array
    {
        return ['from_staff' => 'boolean', 'read_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<SourcingRequest, $this> */
    public function sourcingRequest(): BelongsTo
    {
        return $this->belongsTo(SourcingRequest::class);
    }
}
