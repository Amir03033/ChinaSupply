<?php

namespace App\Http\Controllers;

use App\Enums\PublicationStatus;
use App\Models\PageTranslation;
use App\Support\Locales;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __invoke(string $slug): View
    {
        $translation = PageTranslation::with('page.translations')
            ->where('locale', app()->getLocale())
            ->where('slug', $slug)
            ->whereHas('page', fn ($q) => $q->where('status', PublicationStatus::Active))
            ->firstOrFail();

        $alternates = $translation->page->translations
            ->mapWithKeys(fn (PageTranslation $t) => [$t->locale => Locales::isSupported($t->locale)
                ? localized_route('pages.show', ['slug' => $t->slug], $t->locale)
                : null])
            ->filter()
            ->all();

        return view('pages.cms', [
            'page' => $translation->page,
            'translation' => $translation,
            'alternates' => $alternates,
        ]);
    }
}
