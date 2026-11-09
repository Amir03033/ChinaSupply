<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Certification;
use App\Models\Supplier;
use App\Support\SupplierDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierDirectoryController extends Controller
{
    public const COMPARE_MAX = 3;

    public function index(Request $request): View
    {
        $filters = SupplierDirectory::filters($request);

        return view('suppliers.index', [
            'filters' => $filters,
            'hasFilters' => SupplierDirectory::hasFilters($filters),
            'suppliers' => SupplierDirectory::search($filters),
            'categories' => Category::active()->roots()->withCurrentTranslation()->orderBy('sort_order')->get(),
            'provinces' => SupplierDirectory::provinces(),
            'certifications' => Certification::orderBy('name')->get(['code', 'name']),
            'compareIds' => $request->session()->get('compare', []),
            'favoriteIds' => $request->user()?->isBuyer() ? $request->user()->favoriteSuppliers()->pluck('suppliers.id')->all() : [],
        ]);
    }

    public function compare(Request $request): View
    {
        $ids = $request->session()->get('compare', []);
        $suppliers = Supplier::publiclyVisible()->whereIn('id', $ids)
            ->with(['translations', 'certifications', 'verificationChecks', 'categories' => fn ($q) => $q->withCurrentTranslation()])
            ->get()->sortBy(fn ($s) => array_search($s->id, $ids))->values();

        return view('suppliers.compare', ['suppliers' => $suppliers]);
    }

    public function toggleCompare(Request $request, Supplier $supplier): RedirectResponse
    {
        abort_unless($supplier->isPubliclyVisible(), 404);
        $ids = $request->session()->get('compare', []);

        if (in_array($supplier->id, $ids, true)) {
            $ids = array_values(array_diff($ids, [$supplier->id]));
        } elseif (count($ids) >= self::COMPARE_MAX) {
            return back()->with('directory_status', __('directory.compare_full', ['max' => self::COMPARE_MAX]));
        } else {
            $ids[] = $supplier->id;
        }

        $request->session()->put('compare', $ids);

        return back();
    }

    public function toggleFavorite(Request $request, Supplier $supplier): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isBuyer() && $supplier->isPubliclyVisible(), 403);

        $user->favoriteSuppliers()->toggle($supplier->id);

        return back();
    }
}
