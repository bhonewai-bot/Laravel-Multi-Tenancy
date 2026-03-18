<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Serves Cloudflare Custom Hostname HTTP validation challenges for pending domains.
 *
 * Cloudflare validates ownership by requesting a well-known HTTP URL on the custom
 * domain before the tenant domain is marked verified. This endpoint intentionally
 * stays outside tenant verification middleware so pending domains can complete the
 * onboarding flow without exposing tenant application data.
 */
class CloudflareHostnameChallengeController extends Controller
{
    /**
     * Return the expected challenge body for the current request host and hostname ID.
     *
     * Side effects:
     * - Reads from the central domains table.
     *
     * @param  Request  $request
     * @param  string  $hostnameId
     * @return Response
     */
    public function __invoke(Request $request, string $hostnameId): Response
    {
        $domain = Domain::query()
            ->where('domain', $request->getHost())
            ->first();

        if (! $domain) {
            abort(404);
        }

        $payloadHostnameId = data_get($domain->cf_payload, 'result.id');
        $challengeBody = data_get($domain->cf_payload, 'result.ownership_verification_http.http_body');

        // The challenge must match both the requested host and Cloudflare hostname ID to avoid
        // serving another domain's token on shared infrastructure.
        if ($payloadHostnameId !== $hostnameId || ! is_string($challengeBody) || $challengeBody === '') {
            abort(404);
        }

        return response($challengeBody, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
