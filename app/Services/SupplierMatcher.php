<?php

namespace App\Services;

use App\Enums\SupplierPipelineStatus;
use App\Models\Category;
use App\Models\SourcingRequest;
use App\Models\Supplier;
use Illuminate\Support\Collection;

/**
 * Suggests suppliers for a sourcing request. Transparent, rule-based scoring
 * (no paid boosting): category fit, MOQ fit, verification level, publication.
 * Staff always decide; suggestions are only a starting point.
 */
class SupplierMatcher
{
    /**
     * @return Collection<int, array{supplier: Supplier, score: int, reasons: list<string>}>
     */
    public function suggest(SourcingRequest $request, int $limit = 10): Collection
    {
        $exclude = $request->matches()->pluck('supplier_id');
        $categoryIds = $this->categoryScope($request->category);

        $candidates = Supplier::query()
            ->whereNotIn('pipeline_status', [SupplierPipelineStatus::Rejected, SupplierPipelineStatus::Inactive])
            ->whereNotIn('id', $exclude)
            ->when($categoryIds !== null, fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $categoryIds)))
            ->with('categories:id')
            ->limit(300)
            ->get();

        return $candidates
            ->map(function (Supplier $s) use ($request) {
                $score = 0;
                /** @var list<string> $reasons */
                $reasons = [];

                if ($request->category_id && $s->categories->contains('id', $request->category_id)) {
                    $score += 30;
                    $reasons[] = 'exact category';
                } elseif ($request->category_id) {
                    $score += 15;
                    $reasons[] = 'related category';
                }
                if ($s->moq_min !== null && $s->moq_min <= $request->quantity) {
                    $score += 20;
                    $reasons[] = 'MOQ fits';
                } elseif ($s->moq_min !== null) {
                    $score -= 15;
                    $reasons[] = 'MOQ above quantity';
                }
                if (in_array($request->customization, ['oem', 'logo'], true) && $s->offers_oem) {
                    $score += 10;
                    $reasons[] = 'offers OEM';
                }
                if ($request->customization === 'odm' && $s->offers_odm) {
                    $score += 10;
                    $reasons[] = 'offers ODM';
                }
                $score += $s->verification_level * 10;
                if ($s->verification_level > 0) {
                    $reasons[] = 'level '.$s->verification_level;
                }
                if ($s->isPubliclyVisible()) {
                    $score += 10;
                    $reasons[] = 'published';
                }

                return ['supplier' => $s, 'score' => $score, 'reasons' => $reasons];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * Category, its descendants and its siblings' subtree via the parent.
     *
     * @return ?list<int>
     */
    private function categoryScope(?Category $category): ?array
    {
        if (! $category) {
            return null;
        }

        $anchor = $category->parent ?? $category;

        return Category::descendantsOf($anchor)->pluck('id')->push($anchor->id)->unique()->values()->all();
    }
}
