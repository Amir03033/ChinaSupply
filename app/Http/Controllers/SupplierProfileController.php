<?php

namespace App\Http\Controllers;

use App\Enums\VerificationCheckType;
use App\Models\Supplier;
use App\Support\Locales;
use Illuminate\View\View;

class SupplierProfileController extends Controller
{
    public function show(string $slug): View
    {
        $supplier = Supplier::publiclyVisible()
            ->where('slug', $slug)
            ->with([
                'translations',
                'categories' => fn ($q) => $q->active()->withCurrentTranslation(),
                'products' => fn ($q) => $q->where('is_published', true),
                'certifications',
                'verificationChecks',
            ])
            ->firstOrFail();

        // Public verification panel: every check type, including the ones NOT done.
        $checks = collect(VerificationCheckType::cases())->map(function (VerificationCheckType $type) use ($supplier) {
            $check = $supplier->verificationChecks->first(fn ($c) => $c->type === $type);

            if ($check === null) {
                return ['type' => $type, 'state' => 'not_checked', 'checked_at' => null, 'method' => null];
            }

            // Failed checks are not published as such, and never shown as passed.
            $state = match (true) {
                $check->isValidAt() => 'passed',
                $check->isExpired() => 'expired',
                default => 'not_checked',
            };

            return [
                'type' => $type,
                'state' => $state,
                'checked_at' => $state === 'passed' ? $check->checked_at : null,
                'method' => $state === 'passed' ? $check->method : null,
            ];
        });

        $capabilitiesVerified = $supplier->verificationChecks
            ->first(fn ($c) => $c->type === VerificationCheckType::ProductionCapabilities)?->isValidAt() ?? false;

        $alternates = collect(Locales::codes())->mapWithKeys(fn ($l) => [$l => localized_route('suppliers.show', ['slug' => $supplier->slug], $l)])->all();

        return view('suppliers.show', compact('supplier', 'checks', 'capabilitiesVerified', 'alternates'));
    }
}
