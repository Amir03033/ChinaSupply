<?php

namespace App\Models;

use App\Enums\ContactTopic;
use App\Enums\EnquiryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property ContactTopic $topic
 * @property EnquiryStatus $status
 * @property string $name
 * @property string $email
 * @property ?string $company
 * @property ?int $handled_by
 * @property ?Carbon $handled_at
 */
class ContactEnquiry extends Model
{
    use LogsActivity;

    protected $fillable = ['topic', 'name', 'email', 'company', 'phone', 'message', 'locale', 'status', 'ip_address'];

    protected function casts(): array
    {
        return [
            'topic' => ContactTopic::class,
            'status' => EnquiryStatus::class,
            'handled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'handled_by'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
