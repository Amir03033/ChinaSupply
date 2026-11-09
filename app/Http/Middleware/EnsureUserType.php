<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps buyer, supplier and staff areas strictly separate. A user hitting
 * another type's area is redirected to their own home instead.
 */
class EnsureUserType
{
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = $request->user();

        if (! $user || $user->type->value !== $type) {
            return redirect()->to(self::homeFor($user));
        }

        return $next($request);
    }

    public static function homeFor(?User $user): string
    {
        return match ($user?->type->value) {
            'staff' => url('/admin'),
            'supplier' => route('supplier.portal'),
            'buyer' => route('account'),
            default => route('login'),
        };
    }
}
