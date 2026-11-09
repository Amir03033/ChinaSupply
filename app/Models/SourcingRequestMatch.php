<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $sourcing_request_id
 * @property int $supplier_id
 * @property MatchStatus $status
 * @property ?int $unit_price_cents
 * @property ?string $currency
 * @property ?int $moq
 * @property ?int $lead_time_days
 * @property ?int $sample_cost_cents
 * @property ?string $incoterm
 * @property ?string $payment_terms
 * @property ?Carbon $quote_valid_until
 * @property ?string $advice
 * @property ?string $internal_notes
 * @property bool $shared_with_buyer
 * @property-read Supplier $supplier
 * @property-read SourcingRequest $sourcingRequest
 */
class SourcingRequestMatch extends Model
{
    use LogsActivity;

    protected $fillable = [
        'supplier_id', 'status', 'unit_price_cents', 'currency', 'moq', 'lead_time_days', 'sample_cost_cents', 'incoterm',
        'payment_terms', 'quote_valid_until', 'advice', 'internal_notes', 'shared_with_buyer', 'sort_order', 'added_by',
        'contacted_at', 'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MatchStatus::class,
            'shared_with_buyer' => 'boolean',
            'quote_valid_until' => 'date',
            'contacted_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<SourcingRequest, $this> */
    public function sourcingRequest(): BelongsTo
    {
        return $this->belongsTo(SourcingRequest::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function hasQuote(): bool
    {
        return $this->unit_price_cents !== null || $this->moq !== null || $this->lead_time_days !== null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'unit_price_cents', 'moq', 'lead_time_days', 'shared_with_buyer'])
            ->logOnlyDirty()->dontLogEmptyChanges();
    }
}
