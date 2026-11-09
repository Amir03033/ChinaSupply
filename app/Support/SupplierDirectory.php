<?php

namespace App\Support;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Public supplier search. Only publicly visible suppliers are ever queried.
 * Ordering never depends on payment: relevance, verification level, name.
 */
class SupplierDirectory
{
    public const PER_PAGE = 24;

    public const MOQ_STEPS = [100, 500, 1000, 5000];

    /**
     * @return array{q: string, category: ?string, province: ?string, type: ?string, moq_max: ?int, oem: bool, odm: bool, level: ?int, cert: ?string, sort: string}
     */
    public static function filters(Request $request): array
    {
        $int = fn (string $k, array $allowed) => in_array((int) $request->query($k), $allowed, true) ? (int) $request->query($k) : null;

        return [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 80),
            'category' => $request->filled('category') ? (string) $request->query('category') : null,
            'province' => $request->filled('province') ? mb_substr((string) $request->query('province'), 0, 100) : null,
            'type' => in_array($request->query('type'), ['manufacturer', 'trading_company', 'hybrid'], true) ? (string) $request->query('type') : null,
            'moq_max' => $int('moq_max', self::MOQ_STEPS),
            'oem' => $request->boolean('oem'),
            'odm' => $request->boolean('odm'),
            'level' => $int('level', [1, 2, 3]),
            'cert' => $request->filled('cert') ? mb_substr((string) $request->query('cert'), 0, 40) : null,
            'sort' => in_array($request->query('sort'), ['level', 'newest'], true) ? (string) $request->query('sort') : 'relevance',
        ];
    }

    /**
     * @param  array<string, mixed>  $f
     */
    public static function hasFilters(array $f): bool
    {
        return $f['q'] !== '' || $f['category'] || $f['province'] || $f['type'] || $f['moq_max'] || $f['oem'] || $f['odm'] || $f['level'] || $f['cert'];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return LengthAwarePaginator<int, Supplier>
     */
    public static function search(array $f): LengthAwarePaginator
    {
        $query = Supplier::publiclyVisible()->with(['translations', 'categories' => fn ($q) => $q->withCurrentTranslation()]);

        if ($f['q'] !== '') {
            self::applyText($query, $f['q']);
        }

        if ($f['category'] && ($category = self::category($f['category']))) {
            $ids = Category::descendantsOf($category)->active()->pluck('id')->push($category->id);
            $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $ids));
        }

        $query
            ->when($f['province'], fn ($q, $p) => $q->where('province', $p))
            ->when($f['type'], fn ($q, $t) => $q->where('company_type', $t))
            ->when($f['moq_max'], fn ($q, $m) => $q->whereNotNull('moq_min')->where('moq_min', '<=', $m))
            ->when($f['oem'], fn ($q) => $q->where('offers_oem', true))
            ->when($f['odm'], fn ($q) => $q->where('offers_odm', true))
            ->when($f['level'], fn ($q, $l) => $q->where('verification_level', '>=', $l))
            ->when($f['cert'], fn ($q, $code) => $q->whereHas('certifications', fn ($c) => $c
                ->where('status', 'verified')
                ->where(fn ($v) => $v->whereNull('valid_until')->orWhere('valid_until', '>=', now()->toDateString()))
                ->whereHas('certification', fn ($cc) => $cc->where('code', $code))));

        match ($f['sort']) {
            'newest' => $query->orderByDesc('published_at'),
            'level' => $query->orderByDesc('verification_level')->orderBy('name'),
            default => $f['q'] === '' ? $query->orderByDesc('verification_level')->orderBy('name') : $query,
        };

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    private static function applyText(Builder $query, string $q): void
    {
        $like = '%'.addcslashes($q, '%_\\').'%';
        $categoryIds = CategoryTranslation::where('name', 'like', $like)->pluck('category_id');
        $mysql = DB::connection()->getDriverName() === 'mysql' && mb_strlen($q) >= 3;

        $query->where(function (Builder $w) use ($q, $like, $categoryIds, $mysql) {
            $w->where('name', 'like', $like)->orWhere('name_local', 'like', $like)
                ->orWhereHas('translations', fn ($t) => $mysql
                    ? $t->whereFullText(['description', 'capabilities'], $q)
                    : $t->where('description', 'like', $like)->orWhere('capabilities', 'like', $like))
                ->orWhereHas('products', fn ($p) => $p->where('is_published', true)->where('name', 'like', $like));

            if ($categoryIds->isNotEmpty()) {
                $w->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $categoryIds));
            }
        });

        // Relevance: name matches first, then verification level.
        $query->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$like])->orderByDesc('verification_level')->orderBy('name');
    }

    public static function category(string $slug): ?Category
    {
        $translation = CategoryTranslation::where('locale', app()->getLocale())->where('slug', $slug)->first();
        $category = $translation ? Category::find($translation->category_id) : null;

        return $category?->isPubliclyVisible() ? $category : null;
    }

    /** @return list<string> */
    public static function provinces(): array
    {
        return Supplier::publiclyVisible()->whereNotNull('province')->distinct()->orderBy('province')->pluck('province')->all();
    }
}
