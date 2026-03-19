<?php

namespace App\Http\Middleware;

use App\Support\HostResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectInvalidTenantHost
{
    public function __construct(
        private HostResolver $hosts
    ) {}

    /**
     * Enforce a strict tenant-host policy:
     * - central hosts never enter tenant routes
     * - unknown/deleted hosts return 404
     * - known but unverified hosts return 403
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if ($this->hosts->isCentralHost($host)) {
            abort(404);
        }

        $domain = $this->hosts->findTenantDomain($host);

        if (!$domain) {
            abort(404);
        }

        if (!$this->hosts->canServeTenantHost($host)) {
            abort(403, 'This domain is not verified.');
        }

        return $next($request);
    }
}
