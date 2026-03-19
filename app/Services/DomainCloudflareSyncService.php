<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Facades\Log;

/**
 * Centralizes Cloudflare custom-hostname synchronization for tenant domains.
 *
 * The service owns the rules for creating or refreshing Cloudflare hostnames,
 * and persisting the latest activation snapshot onto the local domains table.
 *
 * Request-serving code must not call Cloudflare directly. Middleware and route
 * host checks should only read the persisted domain state written by this service.
 */
class DomainCloudflareSyncService
{
    public function __construct(
        private CloudflareService $cloudflareService
    ) {}

    /**
     * Create or refresh the Cloudflare hostname backing a tenant domain.
     *
     * Side effects:
     * - Calls Cloudflare.
     * - Writes Cloudflare status fields and verification state to the central domains table.
     *
     * @param  Domain  $domain
     * @param  bool  $createWhenMissing
     * @return Domain
     */
    public function sync(Domain $domain, bool $createWhenMissing = false): Domain
    {
        $action = $domain->cf_hostname_id ? 'refresh' : 'create';

        $this->logCloudflareSync('info', 'cloudflare.hostname.sync_started', $domain, [
            'action' => $action,
            'create_when_missing' => $createWhenMissing,
        ]);

        // Missing hostname ids are only acceptable during the initial create call.
        $cf = $domain->cf_hostname_id
            ? $this->cloudflareService->getHostname($domain->cf_hostname_id)
            : ($createWhenMissing
                ? $this->cloudflareService->createHostname($domain->domain)
                : throw new \RuntimeException('Cloudflare hostname ID is missing.'));

        $domain->fill($this->cloudflareService->mapStatuses($cf));
        $domain->cf_last_checked_at = now();
        $domain->verified_at = $this->shouldMarkVerified($domain) ? now() : null;
        $domain->save();

        $this->logCloudflareSync('info', 'cloudflare.hostname.sync_completed', $domain, [
            'action' => $action,
            'verified_now' => $domain->verified_at !== null,
        ]);

        return $domain;
    }

    /**
     * Determine whether background polling should continue for the domain.
     *
     * @param  Domain  $domain
     * @return bool
     */
    public function shouldRetry(Domain $domain): bool
    {
        return $domain->cf_hostname_id !== null
            && $domain->verified_at === null
            && ! $domain->cf_error;
    }

    /**
     * Determine whether the current Cloudflare state is strong enough to trust the domain.
     *
     * @param  Domain  $domain
     * @return bool
     */
    private function shouldMarkVerified(Domain $domain): bool
    {
        return $domain->cf_hostname_status === 'active'
            && $domain->cf_ssl_status === 'active';
    }

    /**
     * Emit structured Cloudflare sync logs for operational debugging.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  Domain  $domain
     * @param  array  $context
     * @return void
     */
    private function logCloudflareSync(string $level, string $message, Domain $domain, array $context = []): void
    {
        Log::{$level}($message, array_merge([
            'tenant_id' => $domain->tenant_id,
            'domain' => $domain->domain,
            'cf_hostname_id' => $domain->cf_hostname_id,
            'cf_hostname_status' => $domain->cf_hostname_status,
            'cf_ssl_status' => $domain->cf_ssl_status,
            'verified_at' => optional($domain->verified_at)?->toIso8601String(),
            'cf_last_checked_at' => optional($domain->cf_last_checked_at)?->toIso8601String(),
            'cf_error' => $domain->cf_error,
        ], $context));
    }
}
