<?php

namespace App\Services;

use App\Enums\VerificationCheckType as T;
use App\Models\Supplier;
use App\Models\VerificationCheck;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The ONLY place that decides a supplier's verification level.
 *
 * Inputs are exclusively the supplier's verification checks. Payments,
 * subscriptions or sponsored placements are never read here — this is
 * enforced by a test.
 *
 * Level 1 Identity checked:  company identity + contact + website/domain
 * Level 2 Business verified: level 1 + business registration + business address
 * Level 3 Factory verified:  level 2 + factory existence (video tour or on-site)
 *
 * A check only counts while it is passed and not expired.
 */
class VerificationLevelCalculator
{
    /** @var array<int, list<T>> */
    public const REQUIREMENTS = [
        1 => [T::CompanyIdentity, T::Contact, T::WebsiteDomain],
        2 => [T::BusinessRegistration, T::BusinessAddress],
        3 => [T::FactoryExistence],
    ];

    /** Days before expiry at which a supplier is flagged for review. */
    public const REVIEW_WINDOW_DAYS = 30;

    /**
     * @param  Collection<int, VerificationCheck>  $checks
     * @return array{level: int, review_required: bool, last_verified_at: ?Carbon}
     */
    public function calculate(Collection $checks, ?Carbon $at = null): array
    {
        $at ??= now();
        $byType = $checks->keyBy(fn (VerificationCheck $c) => $c->type->value);
        $valid = fn (T $type) => ($c = $byType->get($type->value)) !== null && $c->isValidAt($at);

        $level = 0;
        foreach (self::REQUIREMENTS as $candidate => $types) {
            if (collect($types)->every($valid)) {
                $level = $candidate;
            } else {
                break;
            }
        }

        // Review needed when a check that supports the current level (or the
        // level just above it) expires soon, has expired, or is back in review.
        $relevant = collect(self::REQUIREMENTS)->filter(fn ($t, $l) => $l <= min($level + 1, 3))->flatten()->map->value->all();
        $reviewRequired = $checks->contains(fn (VerificationCheck $c) => in_array($c->type->value, $relevant, true) && (
            $c->isExpired()
            || ($c->isValidAt($at) && $c->expires_at !== null && $c->expires_at->lessThanOrEqualTo($at->copy()->addDays(self::REVIEW_WINDOW_DAYS)))
            || ($c->status->value === 'pending' && $c->checked_at !== null)
        ));

        $lastVerified = $checks->filter(fn (VerificationCheck $c) => $c->isValidAt($at))->max('checked_at');

        return ['level' => $level, 'review_required' => $reviewRequired, 'last_verified_at' => $lastVerified];
    }

    /**
     * Recalculate and persist the cached level on the supplier.
     */
    public function refresh(Supplier $supplier): Supplier
    {
        $result = $this->calculate($supplier->verificationChecks()->get());

        $supplier->forceFill([
            'verification_level' => $result['level'],
            'verification_review_required' => $result['review_required'],
            'last_verified_at' => $result['last_verified_at'],
        ])->save();

        return $supplier;
    }
}
