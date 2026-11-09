<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featured = Category::active()->roots()->where('is_featured', true)
            ->withCurrentTranslation()
            ->withCount(['children' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        return view('home', ['featuredCategories' => $featured]);
    }
}
