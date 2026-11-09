<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SourcingRequestController;
use App\Http\Controllers\SupplierDirectoryController;
use App\Http\Controllers\SupplierDocumentController;
use App\Http\Controllers\SupplierProfileController;
use App\Http\Controllers\SupplierRegistrationController;
use App\Http\Middleware\EnsureUserType;
use App\Http\Middleware\SetLocaleFromRoute;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (Request $request) => redirect()->to(
    localized_route('home', [], $request->session()->get('locale') ?? Locales::preferred($request))
))->name('root');

/*
| Public routes, registered once per locale with translated URL segments
| (lang/{locale}/routes.php). Names are prefixed with the locale, e.g.
| "nl.categories.show"; use localized_route('categories.show', ...).
*/
foreach (Locales::codes() as $locale) {
    $segment = fn (string $key) => trans('routes.'.$key, [], $locale);

    Route::prefix($locale)
        ->name($locale.'.')
        ->middleware(SetLocaleFromRoute::class.':'.$locale)
        ->group(function () use ($segment) {
            Route::get('/', HomeController::class)->name('home');
            Route::get($segment('categories'), [CategoryController::class, 'index'])->name('categories.index');
            Route::get($segment('categories').'/{slug}', [CategoryController::class, 'show'])->name('categories.show');
            Route::view($segment('how_it_works'), 'pages.how-it-works')->name('how-it-works');
            Route::view($segment('verification'), 'pages.verification')->name('verification');
            Route::view($segment('for_suppliers'), 'pages.for-suppliers')->name('for-suppliers');
            Route::view($segment('about'), 'pages.about')->name('about');
            Route::get($segment('contact'), [ContactController::class, 'create'])->name('contact');
            Route::post($segment('contact'), [ContactController::class, 'store'])
                ->middleware('throttle:contact')->name('contact.store');
            Route::get($segment('info').'/{slug}', PageController::class)->name('pages.show');
            Route::get($segment('suppliers'), [SupplierDirectoryController::class, 'index'])->name('suppliers.index');
            Route::get($segment('suppliers').'/'.$segment('compare'), [SupplierDirectoryController::class, 'compare'])->name('suppliers.compare');
            Route::get($segment('suppliers').'/{slug}', [SupplierProfileController::class, 'show'])->name('suppliers.show');
            Route::view($segment('sourcing_request'), 'sourcing.wizard')->name('sourcing.create');
            Route::get($segment('supplier_register'), [SupplierRegistrationController::class, 'create'])->name('supplier.register');
            Route::post($segment('supplier_register'), [SupplierRegistrationController::class, 'store'])
                ->middleware(['guest', 'throttle:5,1'])->name('supplier.register.store');
        });
}

Route::post('/compare/{supplier:slug}', [SupplierDirectoryController::class, 'toggleCompare'])
    ->middleware('throttle:60,1')->name('compare.toggle');
Route::get('/requests/{sourcingRequest:uuid}/track', [SourcingRequestController::class, 'track'])
    ->middleware(['signed', 'throttle:30,1'])->name('requests.track');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/staff/request-files/{file}', [SourcingRequestController::class, 'staffFile'])->name('staff.request-files.download');
    Route::post('/favorites/{supplier:slug}', [SupplierDirectoryController::class, 'toggleFavorite'])->name('favorites.toggle');
    Route::middleware(EnsureUserType::class.':buyer')->prefix('account')->name('account.')->group(function () {
        Route::get('/requests', [SourcingRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{reference}', [SourcingRequestController::class, 'show'])->name('requests.show');
        Route::post('/requests/{reference}/messages', [SourcingRequestController::class, 'message'])->middleware('throttle:20,1')->name('requests.message');
        Route::post('/requests/{reference}/select/{match}', [SourcingRequestController::class, 'select'])->name('requests.select');
        Route::get('/requests/{reference}/files/{file}', [SourcingRequestController::class, 'file'])->name('requests.file');
        Route::get('/favorites', [SourcingRequestController::class, 'favorites'])->name('favorites');
    });
    Route::get('/account', [SourcingRequestController::class, 'dashboard'])->middleware(EnsureUserType::class.':buyer')->name('account');
    Route::view('/supplier-portal', 'supplier.portal')->middleware(EnsureUserType::class.':supplier')->name('supplier.portal');
    Route::get('/documents/{document}/download', [SupplierDocumentController::class, 'download'])
        ->middleware('throttle:60,1')->name('documents.download');
});
