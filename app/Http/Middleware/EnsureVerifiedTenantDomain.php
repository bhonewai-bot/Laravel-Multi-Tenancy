<?php

namespace App\Http\Middleware;

use App\Services\TenantDomainService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents tenant traffic from being served on unverified custom domains.
 */
class EnsureVerifiedTenantDomain
{
    /**
     * Create a new middleware instance.
     *
     * @param  TenantDomainService  $domainService
     */
    public function __construct(
        private TenantDomainService $domainService
    ) {}

    /**
     * Ensure the current host is valid for the resolved tenant.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (!$tenant) {
            return $next($request);
        }

        $domain = $request->getHost();

        // WARNING: This host check is the last guardrail against serving one tenant on another tenant's custom domain.
        if (!$this->domainService->canUseAsTenantDomain($tenant, $domain)) {
            abort(403, 'This domain is not verified.');
        }

        return $next($request);
    }
}
