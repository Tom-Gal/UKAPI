<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user?->is_admin, 403);

        if ($user->two_factor_confirmed_at === null) {
            return to_route('security.edit')->with(
                'status',
                'Two-factor authentication is required before an admin account can access operations.',
            );
        }

        return $next($request);
    }
}
