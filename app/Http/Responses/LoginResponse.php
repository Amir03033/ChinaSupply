<?php

namespace App\Http\Responses;

use App\Http\Middleware\EnsureUserType;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        // Staff work in Filament; buyers and suppliers in their own areas.
        $home = EnsureUserType::homeFor($request->user());

        return redirect()->intended($home);
    }
}
