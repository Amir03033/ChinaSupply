<?php

namespace App\Console\Commands;

use App\Enums\VerificationCheckStatus;
use App\Models\Supplier;
use App\Services\VerificationLevelCalculator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('suppliers:refresh-verification')]
#[Description('Recalculate verification levels for suppliers with expiring/expired checks and create re-verification tasks')]
class RefreshVerificationLevels extends Command
{
    public const TASK_TITLE = 'Re-verification due';

    public function handle(VerificationLevelCalculator $calculator): int
    {
        $horizon = now()->addDays(VerificationLevelCalculator::REVIEW_WINDOW_DAYS);
        $count = 0;

        Supplier::query()
            ->whereHas('verificationChecks', fn ($q) => $q->where('status', VerificationCheckStatus::Passed)->where('expires_at', '<=', $horizon))
            ->orWhere('verification_review_required', true)
            ->with('verificationChecks')
            ->chunkById(100, function ($suppliers) use ($calculator, &$count) {
                foreach ($suppliers as $supplier) {
                    $calculator->refresh($supplier);
                    $count++;

                    if ($supplier->verification_review_required
                        && $supplier->tasks()->open()->where('title', self::TASK_TITLE)->doesntExist()) {
                        $soonest = $supplier->verificationChecks->whereNotNull('expires_at')->min('expires_at');
                        $supplier->tasks()->create([
                            'title' => self::TASK_TITLE,
                            'description' => 'One or more verification checks expire soon, have expired or need re-review.',
                            'due_at' => $soonest && $soonest->isFuture() ? $soonest : now(),
                            'assigned_to' => $supplier->assigned_to,
                        ]);
                    }
                }
            });

        $this->info("Refreshed {$count} suppliers.");

        return self::SUCCESS;
    }
}
