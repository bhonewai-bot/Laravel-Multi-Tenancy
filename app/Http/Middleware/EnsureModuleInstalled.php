<?php

namespace App\Http\Middleware;

use App\Services\TenantModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks tenant routes that depend on a module the tenant has not installed.
 */
class EnsureModuleInstalled
{
    public function __construct(
        private TenantModuleRegistry $registry
    ) {}

    /**
     * Ensure the requested module is present in the tenant's installed module list.
     *
     * This middleware relies on tenant context already being initialized. Without that,
     * module access checks could read the wrong tenant metadata.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $tenant = tenant();

        if (! $tenant) {
            abort(404, 'Tenant context is required.');
        }

        $installedModules = $this->registry->getInstalledModules($tenant);
        $targetModule = Str::lower($module);

        if (! in_array($targetModule, $installedModules, true)) {
            abort(403, "Access denied. You have not installed the '{$targetModule}' module.");
        }

        return $next($request);
    }
}
