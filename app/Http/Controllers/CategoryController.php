<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $search = mb_substr($search, 0, 80);

        if ($search !== '') {
            $matches = Category::active()
                ->withCurrentTranslation()
                ->whereHas('translations', fn ($q) => $q
                    ->where('locale', app()->getLocale())
                    ->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
                ->orderBy('depth')->orderBy('sort_order')
                ->limit(50)
                ->get()
                ->filter(fn (Category $c) => $c->isPubliclyVisible())
                ->values();

            return view('categories.index', ['search' => $search, 'matches' => $matches, 'roots' => collect()]);
        }

        $roots = Category::active()->roots()
            ->withCurrentTranslation()
            ->with(['children' => fn ($q) => $q->active()->withCurrentTranslation()])
            ->orderBy('sort_order')
            ->get();

        return view('categories.index', ['search' => '', 'matches' => collect(), 'roots' => $roots]);
    }

    public function show(string $slug): View
    {
        $translation = CategoryTranslation::where('locale', app()->getLocale())->where('slug', $slug)->firstOrFail();

        /** @var Category $category */
        $category = Category::with('translations')->findOrFail($translation->category_id);
        abort_unless($category->isPubliclyVisible(), 404);

        $children = $category->children()->active()->withCurrentTranslation()
            ->withCount(['children' => fn ($q) => $q->active()])
            ->get();

        $alternates = collect(Locales::codes())
            ->mapWithKeys(fn (string $locale) => [$locale => ($s = $category->slug($locale))
                ? localized_route('categories.show', ['slug' => $s], $locale)
                : null])
            ->filter()
            ->all();

        return view('categories.show', [
            'category' => $category,
            'translation' => $translation,
            'ancestors' => $category->ancestors(),
            'children' => $children,
            'alternates' => $alternates,
        ]);
    }
}
