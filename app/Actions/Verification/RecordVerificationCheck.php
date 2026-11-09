<?php

namespace App\Actions\Verification;

use App\Enums\Permission;
use App\Enums\VerificationCheckStatus;
use App\Enums\VerificationCheckType;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VerificationCheck;
use App\Services\VerificationLevelCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordVerificationCheck
{
    public function __construct(private VerificationLevelCalculator $calculator) {}

    /**
     * @param  array{status: VerificationCheckStatus|string, method?: ?string, internal_notes?: ?string, evidence_document_id?: ?int, expires_at?: Carbon|string|null}  $data
     */
    public function handle(Supplier $supplier, VerificationCheckType $type, array $data, User $reviewer): VerificationCheck
    {
        if (! $reviewer->isStaff() || ! $reviewer->can(Permission::ManageVerification->value)) {
            throw new AuthorizationException('Only verification staff can record checks.');
        }

        $status = $data['status'] instanceof VerificationCheckStatus ? $data['status'] : VerificationCheckStatus::from($data['status']);

        if ($status === VerificationCheckStatus::Passed && blank($data['method'] ?? null)) {
            throw ValidationException::withMessages(['method' => 'Describe how this was checked; buyers see this explanation.']);
        }
        if ($status === VerificationCheckStatus::Failed && blank($data['internal_notes'] ?? null)) {
            throw ValidationException::withMessages(['internal_notes' => 'Record why this check failed.']);
        }
        if (filled($data['evidence_document_id'] ?? null)
            && ! $supplier->documents()->whereKey($data['evidence_document_id'])->exists()) {
            throw ValidationException::withMessages(['evidence_document_id' => 'Evidence must belong to this supplier.']);
        }

        return DB::transaction(function () use ($supplier, $type, $data, $status, $reviewer) {
            $check = VerificationCheck::firstOrNew(['supplier_id' => $supplier->id, 'type' => $type]);
            $check->fill([
                'method' => $data['method'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'evidence_document_id' => $data['evidence_document_id'] ?? null,
            ]);
            $check->status = $status;
            $check->reviewer_id = $reviewer->id;
            $check->checked_at = now();
            $check->expires_at = match (true) {
                $status !== VerificationCheckStatus::Passed => null,
                filled($data['expires_at'] ?? null) => Carbon::parse($data['expires_at']),
                $type->defaultValidityMonths() !== null => now()->addMonths($type->defaultValidityMonths()),
                default => null,
            };

            if ($check->expires_at !== null && $check->expires_at->isPast()) {
                throw ValidationException::withMessages(['expires_at' => 'Expiry date must be in the future.']);
            }

            $check->save();
            $this->calculator->refresh($supplier);

            return $check;
        });
    }
}
