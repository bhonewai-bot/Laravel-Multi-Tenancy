<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
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
