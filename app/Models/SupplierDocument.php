<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $supplier_id
 * @property DocumentType $type
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property string $sha256
 * @property DocumentStatus $status
 * @property ?Carbon $valid_until
 * @property bool $is_public
 * @property-read Supplier $supplier
 */
class SupplierDocument extends Model
{
    use LogsActivity;

    protected $fillable = ['type', 'disk', 'path', 'original_name', 'mime', 'size', 'sha256', 'status', 'valid_until', 'is_public', 'uploaded_by', 'review_notes'];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'valid_until' => 'date',
            'is_public' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['type', 'status', 'valid_until', 'is_public', 'review_notes'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
