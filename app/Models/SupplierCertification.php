<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $supplier_id
 * @property string $name
 * @property ?string $certificate_number
 * @property ?string $issued_by
 * @property ?Carbon $valid_until
 * @property string $status pending|verified|rejected
 */
class SupplierCertification extends Model
{
    use LogsActivity;

    protected $fillable = ['certification_id', 'name', 'certificate_number', 'issued_by', 'valid_until', 'document_id'];

    protected function casts(): array
    {
        return ['valid_until' => 'date'];
    }

    /** @return BelongsTo<Certification, $this> */
    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }

    /** @return BelongsTo<SupplierDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(SupplierDocument::class, 'document_id');
    }

    public function isVerifiedAndValid(): bool
    {
        return $this->status === 'verified' && ($this->valid_until === null || $this->valid_until->isFuture());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'certificate_number', 'valid_until', 'status'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
