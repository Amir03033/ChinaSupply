<?php

namespace App\Http\Controllers;

use App\Actions\Suppliers\RegisterSupplier;
use App\Http\Middleware\EnsureUserType;
use App\Http\Requests\RegisterSupplierRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupplierRegistrationController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->to(EnsureUserType::homeFor(auth()->user()));
        }

        return view('supplier.register');
    }

    public function store(RegisterSupplierRequest $request, RegisterSupplier $register): RedirectResponse
    {
        $user = $register->handle($request->safe()->only(['company_name', 'country', 'name', 'email', 'password']), app()->getLocale());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('supplier.portal');
    }
}
