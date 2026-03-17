<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks tenant routes that depend on a module the tenant has not installed.
 */
class EnsureModuleInstalled
{
    /**
     * Ensure the requested module is present in the tenant's installed module list.
     *
     * This middleware relies on tenant context already being initialized. Without that,
     * module access checks could read the wrong tenant metadata.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string  $module
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $tenant = tenant();

        if (!$tenant) {
            abort(404, 'Tenant context is required.');
        }

        $installedModules = $tenant->getAttribute('installed_modules') ?? [];

        if (!is_array($installedModules)) {
            $installedModules = [];
        }

        // The array is normalized before comparison so route parameters can stay case-insensitive.
        $installedModules = array_map(
            fn ($item) => $item,
            $installedModules
        );

        $targetModule = Str::lower($module);

        if (!in_array($targetModule, $installedModules, true)) {
            abort(403, "Access denied. You have not installed the '{$targetModule}' module.");
        }

        return $next($request);
    }
}
