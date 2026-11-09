<?php

namespace App\Actions\Suppliers;

use App\Enums\DocumentType;
use App\Enums\SupplierPipelineStatus;
use App\Models\Supplier;
use App\Notifications\SupplierSubmittedForReview;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class SubmitSupplierOnboarding
{
    /**
     * @return list<string> translation keys of missing requirements
     */
    public static function missing(Supplier $supplier): array
    {
        $missing = [];
        foreach (['legal_name', 'registration_number', 'company_type', 'province', 'city', 'address', 'business_email'] as $field) {
            if (blank($supplier->{$field}) || ($field === 'company_type' && $supplier->company_type->value === 'unknown')) {
                $missing[] = 'portal.fields.'.$field;
            }
        }
        if (blank($supplier->description('en')) && blank($supplier->description('nl'))) {
            $missing[] = 'portal.fields.description';
        }
        if ($supplier->categories()->doesntExist()) {
            $missing[] = 'portal.fields.categories';
        }
        if ($supplier->documents()->where('type', DocumentType::BusinessLicense)->doesntExist()) {
            $missing[] = 'portal.fields.business_license';
        }

        return $missing;
    }

    public function handle(Supplier $supplier): Supplier
    {
        if (($missing = self::missing($supplier)) !== []) {
            throw ValidationException::withMessages(['submit' => array_map(fn ($k) => __($k), $missing)]);
        }

        $first = $supplier->onboarding_submitted_at === null;
        $supplier->onboarding_submitted_at = now();

        // Only move forward from early stages; never undo staff progress.
        if (in_array($supplier->pipeline_status, [
            SupplierPipelineStatus::Applied, SupplierPipelineStatus::Discovered, SupplierPipelineStatus::Researching,
            SupplierPipelineStatus::Contacted, SupplierPipelineStatus::Responded, SupplierPipelineStatus::Interested,
            SupplierPipelineStatus::DocumentsRequested,
        ], true)) {
            $supplier->pipeline_status = SupplierPipelineStatus::VerificationPending;
        }
        $supplier->save();

        if ($first) {
            $supplier->tasks()->create([
                'title' => 'Review supplier application',
                'due_at' => now()->addWeekdays(2),
                'assigned_to' => $supplier->assigned_to,
            ]);
            Notification::route('mail', config('chinasupply.mail.staff_notifications'))
                ->notify(new SupplierSubmittedForReview($supplier));
        }

        return $supplier;
    }
}
