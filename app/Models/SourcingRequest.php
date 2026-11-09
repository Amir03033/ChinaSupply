<?php

namespace App\Models;

use App\Enums\SourcingRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property string $reference
 * @property ?int $user_id
 * @property string $contact_name
 * @property string $contact_email
 * @property ?string $company_name
 * @property ?string $phone
 * @property string $locale
 * @property string $title
 * @property string $description
 * @property ?int $category_id
 * @property ?string $specifications
 * @property ?string $materials
 * @property string $customization
 * @property ?array<int, string> $required_certifications
 * @property int $quantity
 * @property ?string $quantity_unit
 * @property ?int $target_price_cents
 * @property string $currency
 * @property ?Carbon $desired_delivery_date
 * @property string $destination_country
 * @property ?string $incoterm
 * @property ?string $additional_requirements
 * @property ?int $preferred_supplier_id
 * @property SourcingRequestStatus $status
 * @property ?int $assigned_to
 * @property Carbon $submitted_at
 * @property ?Carbon $closed_at
 * @property ?Carbon $created_at
 * @property-read ?User $user
 * @property-read ?User $assignee
 * @property-read ?Category $category
 * @property-read ?Supplier $preferredSupplier
 * @property-read Collection<int, SourcingRequestFile> $files
 * @property-read Collection<int, SourcingRequestMatch> $matches
 * @property-read Collection<int, SourcingRequestEvent> $events
 * @property-read Collection<int, SourcingRequestMessage> $messages
 */
class SourcingRequest extends Model
{
    use LogsActivity, SoftDeletes;

    /** Fields a buyer submits through the wizard. */
    public const BUYER_FIELDS = [
        'contact_name', 'contact_email', 'company_name', 'phone', 'title', 'description', 'category_id', 'specifications',
        'materials', 'customization', 'required_certifications', 'quantity', 'quantity_unit', 'target_price_cents', 'currency',
        'desired_delivery_date', 'destination_country', 'incoterm', 'additional_requirements', 'preferred_supplier_id',
    ];

    protected $fillable = self::BUYER_FIELDS;

    protected function casts(): array
    {
        return [
            'status' => SourcingRequestStatus::class,
            'required_certifications' => 'array',
            'desired_delivery_date' => 'date',
            'submitted_at' => 'datetime',
            'closed_at' => 'datetime',
            'quantity' => 'integer',
            'target_price_cents' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SourcingRequest $r) {
            $r->uuid ??= (string) Str::uuid();
            $r->submitted_at ??= now();
            $r->reference ??= static::nextReference();
        });
    }

    /** CS-2026-00042: sequential per year, generated inside a lock to avoid duplicates. */
    public static function nextReference(): string
    {
        $year = now()->year;

        return DB::transaction(function () use ($year) {
            $last = static::withTrashed()->where('reference', 'like', "CS-$year-%")->lockForUpdate()->max('reference');
            $next = $last ? ((int) substr($last, -5)) + 1 : 1;

            return sprintf('CS-%d-%05d', $year, $next);
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }

    /** @return HasMany<SourcingRequestFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(SourcingRequestFile::class);
    }

    /** @return HasMany<SourcingRequestMatch, $this> */
    public function matches(): HasMany
    {
        return $this->hasMany(SourcingRequestMatch::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<SourcingRequestEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(SourcingRequestEvent::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    /** @return HasMany<SourcingRequestMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(SourcingRequestMessage::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return MorphMany<Task, $this> */
    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    /** @return MorphMany<Note, $this> */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', SourcingRequestStatus::open());
    }

    public function belongsToBuyer(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'assigned_to', 'title', 'quantity', 'target_price_cents', 'user_id'])
            ->logOnlyDirty()->dontLogEmptyChanges();
    }
}
