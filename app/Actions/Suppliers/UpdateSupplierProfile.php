<?php

namespace App\Actions\Suppliers;

use App\Enums\VerificationCheckStatus;
use App\Enums\VerificationCheckType;
use App\Models\Supplier;
use App\Services\VerificationLevelCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Applies supplier-submitted profile changes. Only whitelisted fields are
 * written. If a field that a passed verification check relied on changes,
 * that check goes back to "pending" so a badge never claims more than what
 * was actually checked.
 */
class UpdateSupplierProfile
{
    public function __construct(private VerificationLevelCalculator $calculator) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array{description?: ?string, capabilities?: ?string}>  $translations
     * @return list<VerificationCheckType> checks that were reset
     */
    public function handle(Supplier $supplier, array $attributes, array $translations = []): array
    {
        $attributes = array_intersect_key($attributes, array_flip(Supplier::SUPPLIER_EDITABLE));

        return DB::transaction(function () use ($supplier, $attributes, $translations) {
            $supplier->fill($attributes);
            $changed = array_keys($supplier->getDirty());
            $supplier->save();

            foreach ($translations as $locale => $data) {
                $supplier->translations()->updateOrCreate(['locale' => $locale], array_intersect_key($data, array_flip(['description', 'capabilities'])));
            }

            $reset = [];
            if ($changed !== []) {
                foreach ($supplier->verificationChecks()->get() as $check) {
                    if ($check->status === VerificationCheckStatus::Passed && array_intersect($check->type->dependsOnFields(), $changed) !== []) {
                        $check->status = VerificationCheckStatus::Pending;
                        $check->expires_at = null;
                        $check->internal_notes = trim(($check->internal_notes ?? '')."\n[".now()->toDateString().'] Reset: supplier changed '.implode(', ', array_intersect($check->type->dependsOnFields(), $changed)).'.');
                        $check->save();
                        $reset[] = $check->type;
                    }
                }
                if ($reset !== []) {
                    $this->calculator->refresh($supplier);
                }
            }

            return $reset;
        });
    }
}
