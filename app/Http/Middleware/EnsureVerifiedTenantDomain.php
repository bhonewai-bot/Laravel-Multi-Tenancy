<?php

namespace App\Http\Middleware;

use App\Services\TenantDomainService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerifiedTenantDomain
{
    public function __construct(
        private TenantDomainService $domainService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (!$tenant) {
            return $next($request);
        }

        $domain = $request->getHost();

        if (!$this->domainService->canUseAsTenantDomain($tenant, $domain)) {
            abort(403, 'This domain is not verified.');
        }

        return $next($request);
    }
}
