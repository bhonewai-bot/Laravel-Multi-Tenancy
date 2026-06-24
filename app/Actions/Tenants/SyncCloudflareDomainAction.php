<?php

namespace App\Actions\Tenants;

use App\Jobs\SyncPendingCloudflareDomain;
use App\Models\Domain;
use App\Models\Tenant;
use App\Services\DomainCloudflareSyncService;
use App\Services\TenantDomainService;

/**
 * Synchronize central domain metadata with Cloudflare hostname state.
 *
 * Side effects:
 * - May call Cloudflare.
 * - Writes Cloudflare status fields to the central domains table.
 *
 * @param  Tenant  $tenant
 * @param  Domain  $domain
 * @return void
 */
class SyncCloudflareDomainAction
{
    public function __construct(
        private TenantDomainService $domainService,
        private DomainCloudflareSyncService $domainSyncService
    ) {}

    public function execute(Tenant $tenant, Domain $domain): void
    {
        if (! config('cloudflare.enabled') || $this->domainService->isPrimarySubDomain($tenant, $domain->domain)) {
            // Platform-managed subdomains are trusted by convention and do not need custom-hostname status.
            $domain->forceFill([
                'cf_hostname_id' => null,
                'cf_hostname_status' => null,
                'cf_ssl_status' => null,
                'cf_last_checked_at' => null,
                'cf_error' => null,
                'cf_payload' => null,
                'verified_at' => null,
            ])->save();

            return;
        }

        try {
            $this->domainSyncService->sync($domain, createWhenMissing: true);

            if (
                (bool) config('cloudflare.async_polling', false) &&
                $this->domainSyncService->shouldRetry($domain)
            ) {
                SyncPendingCloudflareDomain::dispatch($domain->id);
            }
        } catch (\Throwable $e) {
            // Domain failures are kept as recoverable metadata so operators can retry without recreating the tenant.
            $domain->forceFill([
                'cf_last_checked_at' => now(),
                'cf_error' => $e->getMessage(),
                'verified_at' => null,
            ])->save();
        }
    }
}
