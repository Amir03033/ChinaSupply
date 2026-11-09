<?php

namespace App\Models;

use App\Enums\CommunicationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $supplier_id
 * @property CommunicationChannel $channel
 * @property string $direction inbound|outbound
 * @property ?string $subject
 * @property ?string $body
 * @property Carbon $occurred_at
 * @property-read ?User $user
 * @property-read ?SupplierContact $contact
 */
class SupplierCommunication extends Model
{
    protected $fillable = ['contact_id', 'outreach_template_id', 'channel', 'direction', 'subject', 'body', 'user_id', 'occurred_at'];

    protected function casts(): array
    {
        return ['channel' => CommunicationChannel::class, 'occurred_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Keep the supplier's "last contacted" date in sync with outbound contact.
        static::created(function (SupplierCommunication $c) {
            if ($c->direction === 'outbound') {
                Supplier::whereKey($c->supplier_id)
                    ->where(fn ($q) => $q->whereNull('last_contacted_at')->orWhere('last_contacted_at', '<', $c->occurred_at))
                    ->update(['last_contacted_at' => $c->occurred_at]);
            }
        });
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<SupplierContact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(SupplierContact::class, 'contact_id');
    }
}
