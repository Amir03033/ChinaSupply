<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sourcing_request_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property-read SourcingRequest $sourcingRequest
 */
class SourcingRequestFile extends Model
{
    protected $fillable = ['disk', 'path', 'original_name', 'mime', 'size', 'uploaded_by'];

    /** @return BelongsTo<SourcingRequest, $this> */
    public function sourcingRequest(): BelongsTo
    {
        return $this->belongsTo(SourcingRequest::class);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }
}
