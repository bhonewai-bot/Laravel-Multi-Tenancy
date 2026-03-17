<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces tenant permission checks on authenticated requests.
 */
class EnsureTenantPermission
{
    /**
     * Allow the request when the user has at least one of the required permissions.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string  ...$permissions
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if (empty($permissions)) {
            return $next($request);
        }

        // Any matching permission is sufficient so routes can express alternate authorization paths.
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Access denied. Required permission: ' . implode(', ', $permissions));
    }
}
