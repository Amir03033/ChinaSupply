<?php

namespace App\Models;

use App\Enums\CompanyType;
use App\Enums\SupplierPipelineStatus;
use App\Enums\SupplierSource;
use App\Models\Concerns\HasTranslationRows;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property string $name
 * @property ?string $name_local
 * @property ?string $legal_name
 * @property ?string $registration_number
 * @property CompanyType $company_type
 * @property string $country
 * @property ?string $province
 * @property ?string $city
 * @property ?string $address
 * @property ?string $website
 * @property ?string $business_email
 * @property ?string $business_phone
 * @property bool $show_contact_publicly
 * @property ?int $founded_year
 * @property ?string $employee_range
 * @property ?int $moq_min
 * @property ?string $moq_unit
 * @property ?string $production_capacity
 * @property ?int $lead_time_min_days
 * @property ?int $lead_time_max_days
 * @property bool $offers_oem
 * @property bool $offers_odm
 * @property bool $offers_private_label
 * @property ?array<int, string> $export_markets
 * @property ?array<int, string> $languages
 * @property ?string $logo_path
 * @property ?string $cover_path
 * @property int $verification_level
 * @property bool $verification_review_required
 * @property ?Carbon $last_verified_at
 * @property SupplierPipelineStatus $pipeline_status
 * @property SupplierSource $source
 * @property ?string $source_url
 * @property ?int $assigned_to
 * @property ?Carbon $last_contacted_at
 * @property ?Carbon $next_follow_up_at
 * @property ?string $rejection_reason
 * @property int $onboarding_step
 * @property ?Carbon $onboarding_submitted_at
 * @property ?Carbon $published_at
 * @property ?Carbon $created_at
 * @property-read Collection<int, SupplierTranslation> $translations
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, VerificationCheck> $verificationChecks
 * @property-read Collection<int, SupplierDocument> $documents
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, SupplierCertification> $certifications
 * @property-read Collection<int, User> $users
 * @property-read ?User $assignee
 */
class Supplier extends Model
{
    use HasTranslationRows, LogsActivity, SoftDeletes;

    /**
     * Fields a supplier may edit in the portal. Verification, CRM and
     * publication fields are deliberately absent.
     */
    public const SUPPLIER_EDITABLE = [
        'name', 'name_local', 'legal_name', 'registration_number', 'company_type', 'country', 'province', 'city',
        'address', 'website', 'business_email', 'business_phone', 'founded_year', 'employee_range', 'moq_min',
        'moq_unit', 'production_capacity', 'lead_time_min_days', 'lead_time_max_days', 'offers_oem', 'offers_odm',
        'offers_private_label', 'export_markets', 'languages',
    ];

    protected $fillable = [
        ...self::SUPPLIER_EDITABLE,
        'show_contact_publicly', 'logo_path', 'cover_path', 'source', 'source_url', 'assigned_to', 'next_follow_up_at',
    ];

    protected function casts(): array
    {
        return [
            'company_type' => CompanyType::class,
            'pipeline_status' => SupplierPipelineStatus::class,
            'source' => SupplierSource::class,
            'show_contact_publicly' => 'boolean',
            'offers_oem' => 'boolean',
            'offers_odm' => 'boolean',
            'offers_private_label' => 'boolean',
            'verification_review_required' => 'boolean',
            'export_markets' => 'array',
            'languages' => 'array',
            'verification_level' => 'integer',
            'onboarding_step' => 'integer',
            'last_verified_at' => 'datetime',
            'last_contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'onboarding_submitted_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Supplier $supplier) {
            $supplier->uuid ??= (string) Str::uuid();
            $supplier->slug ??= static::uniqueSlug($supplier->name);
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'supplier';
        if (in_array($base, ['compare', 'vergelijken'], true)) { // reserved directory routes
            $base .= '-supplier';
        }
        $slug = $base;
        $i = 2;
        while (static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /** @return HasMany<SupplierTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(SupplierTranslation::class);
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('is_primary');
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'supplier_users')->withPivot('role')->withTimestamps();
    }

    /** @return HasMany<SupplierContact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    /** @return HasMany<SupplierDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class);
    }

    /** @return HasMany<SupplierCertification, $this> */
    public function certifications(): HasMany
    {
        return $this->hasMany(SupplierCertification::class);
    }

    /** @return HasMany<VerificationCheck, $this> */
    public function verificationChecks(): HasMany
    {
        return $this->hasMany(VerificationCheck::class);
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('sort_order');
    }

    /** @return HasMany<SupplierCommunication, $this> */
    public function communications(): HasMany
    {
        return $this->hasMany(SupplierCommunication::class)->latest('occurred_at');
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

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Public = published in the pipeline AND at least identity-checked.
     * A level that drops to 0 (expired checks) hides the profile again.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('pipeline_status', SupplierPipelineStatus::Published)->where('verification_level', '>=', 1);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->pipeline_status === SupplierPipelineStatus::Published && $this->verification_level >= 1;
    }

    public function description(?string $locale = null): ?string
    {
        return $this->translated('description', $locale);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logExcept(['updated_at', 'created_at', 'onboarding_step'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
