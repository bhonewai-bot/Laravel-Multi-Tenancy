<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\TenantDomainService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Validates whether a domain is permitted to route traffic to this application.
 *
 * This endpoint is used for lightweight domain checks without booting the full tenant UI.
 */
class DomainCheckController extends Controller
{
    /**
     * Validate the shared token and confirm the requested domain is allowed.
     *
     * Side effects:
     * - Reads the central domains table.
     */
    public function __invoke(Request $request, TenantDomainService $domainService): Response
    {
        $configuredToken = (string) env('DOMAIN_CHECK_TOKEN', '');
        $providedToken = (string) ($request->query('token') ?: $request->header('X-Domain-Check-Token', ''));

        if ($configuredToken === '') {
            return response('Domain-check token not configured', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (! hash_equals($configuredToken, $providedToken)) {
            return response('Unauthorized', 403);
        }

        $data = $request->validate([
            'domain' => ['required', 'string', 'max:255'],
        ]);

        $domain = $domainService->normalize($data['domain']);

        // Central domains are trusted by configuration and do not require a verified domain record.
        if ($domainService->isCentralDomain($domain)) {
            return response('OK', 200);
        }

        $exists = Domain::query()
            ->where('domain', $domain)
            ->whereNotNull('verified_at')
            ->exists();

        return $exists ? response('OK', 200) : response('Unauthorized', 404);
    }
}
