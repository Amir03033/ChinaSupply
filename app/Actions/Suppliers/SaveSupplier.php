<?php

namespace App\Actions\Suppliers;

use App\Enums\SupplierPipelineStatus;
use App\Enums\VerificationCheckStatus;
use App\Enums\VerificationCheckType;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

/**
 * Staff-side create/update of a supplier record (Filament, CSV import).
 * Pipeline status and verification are never set here.
 */
class SaveSupplier
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, ?string>>  $translations
     * @param  list<int>|null  $categoryIds  null = leave categories untouched
     */
    public function handle(?Supplier $supplier, array $attributes, array $translations = [], ?array $categoryIds = null): Supplier
    {
        return DB::transaction(function () use ($supplier, $attributes, $translations, $categoryIds) {
            $isNew = $supplier === null;
            $supplier ??= new Supplier;
            $supplier->fill($attributes);
            if ($isNew) {
                $supplier->pipeline_status ??= SupplierPipelineStatus::Discovered;
            }
            $supplier->save();

            if ($isNew) {
                self::ensureCheckRows($supplier);
            }

            foreach ($translations as $locale => $data) {
                $data = array_intersect_key($data, array_flip(['description', 'capabilities']));
                if (collect($data)->filter(fn ($v) => filled($v))->isEmpty()) {
                    $supplier->translations()->where('locale', $locale)->delete();

                    continue;
                }
                $supplier->translations()->updateOrCreate(['locale' => $locale], $data);
            }

            if ($categoryIds !== null) {
                $supplier->categories()->sync(array_map('intval', $categoryIds));
            }

            return $supplier->refresh();
        });
    }

    /**
     * Every supplier carries one row per check type so staff always see the
     * full list, including what has NOT been checked.
     */
    public static function ensureCheckRows(Supplier $supplier): void
    {
        $existing = $supplier->verificationChecks()->pluck('type')->map(fn ($t) => $t instanceof VerificationCheckType ? $t->value : $t)->all();

        foreach (VerificationCheckType::cases() as $type) {
            if (! in_array($type->value, $existing, true)) {
                $check = $supplier->verificationChecks()->make(['type' => $type]);
                $check->status = VerificationCheckStatus::NotChecked;
                $check->save();
            }
        }
    }
}
