<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts access to the configured central super-admin.
 */
class EnsureCentralAdmin
{
    /**
     * Allow the request only when the authenticated user is the central super-admin.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->email !== config('auth.central_admin.email')) {
            abort(403, 'Access denied. Central admin only.');
        }

        return $next($request);
    }
}
