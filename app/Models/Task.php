<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $taskable_type
 * @property int $taskable_id
 * @property string $title
 * @property ?string $description
 * @property ?Carbon $due_at
 * @property ?int $assigned_to
 * @property ?Carbon $completed_at
 * @property-read ?User $assignee
 * @property-read Model $taskable
 */
class Task extends Model
{
    protected $fillable = ['title', 'description', 'due_at', 'assigned_to', 'created_by', 'completed_at'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Supplier.next_follow_up_at = earliest open task due date.
        $sync = function (Task $task) {
            if ($task->taskable_type === (new Supplier)->getMorphClass()) {
                $next = static::where('taskable_type', $task->taskable_type)->where('taskable_id', $task->taskable_id)
                    ->whereNull('completed_at')->min('due_at');
                Supplier::withTrashed()->whereKey($task->taskable_id)->update(['next_follow_up_at' => $next]);
            }
        };
        static::saved($sync);
        static::deleted($sync);
    }

    /** @return MorphTo<Model, $this> */
    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function isOverdue(): bool
    {
        return $this->completed_at === null && $this->due_at !== null && $this->due_at->isPast();
    }
}
