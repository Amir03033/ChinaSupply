<?php

namespace App\Models;

use App\Enums\VerificationCheckStatus;
use App\Enums\VerificationCheckType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $supplier_id
 * @property VerificationCheckType $type
 * @property VerificationCheckStatus $status
 * @property ?string $method
 * @property ?string $internal_notes
 * @property ?int $evidence_document_id
 * @property ?int $reviewer_id
 * @property ?Carbon $checked_at
 * @property ?Carbon $expires_at
 * @property-read ?SupplierDocument $evidence
 * @property-read ?User $reviewer
 */
class VerificationCheck extends Model
{
    use LogsActivity;

    // Status, reviewer and dates are set only through RecordVerificationCheck.
    protected $fillable = ['type', 'method', 'internal_notes', 'evidence_document_id'];

    protected function casts(): array
    {
        return [
            'type' => VerificationCheckType::class,
            'status' => VerificationCheckStatus::class,
            'checked_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<SupplierDocument, $this> */
    public function evidence(): BelongsTo
    {
        return $this->belongsTo(SupplierDocument::class, 'evidence_document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** Passed and not expired at the given moment. */
    public function isValidAt(?Carbon $at = null): bool
    {
        $at ??= now();

        return $this->status === VerificationCheckStatus::Passed
            && ($this->expires_at === null || $this->expires_at->greaterThan($at));
    }

    public function isExpired(): bool
    {
        return $this->status === VerificationCheckStatus::Passed && $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'status', 'method', 'internal_notes', 'evidence_document_id', 'reviewer_id', 'checked_at', 'expires_at'])
            ->logOnlyDirty()->dontLogEmptyChanges();
    }
}
