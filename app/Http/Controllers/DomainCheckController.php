<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\TenantDomainService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DomainCheckController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, TenantDomainService $domainService): Response
    {
        $configuredToken = (string) env('DOMAIN_CHECK_TOKEN', '');
        $providedToken = (string) ($request->query('token') ?: $request->header('X-Domain-Check-Token', ''));

        if ($configuredToken === '') {
            return response('Domain-check token not configured', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!hash_equals($configuredToken, $providedToken)) {
            return response('Unauthorized', 403);
        }

        $data = $request->validate([
            'domain' => ['required', 'string', 'max:255'],
        ]);

        $domain = $domainService->normalize($data['domain']);

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
