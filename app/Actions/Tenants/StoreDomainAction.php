<?php

namespace App\Actions\Tenants;

use App\Jobs\SyncPendingCloudflareDomain;
use App\Models\Domain;
use App\Models\Tenant;
use App\Services\DomainCloudflareSyncService;
use App\Services\TenantDomainService;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Persist a new custom domain for a tenant and sync with Cloudflare.
 *
 * Side effects:
 * - Writes to the central domains table.
 * - May call Cloudflare and dispatch a background polling job.
 */
class StoreDomainAction
{
    public function __construct(
        private TenantDomainService $domainService,
        private DomainCloudflareSyncService $domainSyncService,
    ) {}

    /**
     * @return array{domain: Domain, message: string}
     *
     * @throws RuntimeException When domain validation fails
     */
    public function execute(Tenant $tenant, string $rawDomain): array
    {
        $host = $this->domainService->normalize($rawDomain);

        $this->validate($tenant, $host);

        $domain = Domain::query()->create([
            'tenant_id' => $tenant->id,
            'domain' => $host,
            'verification_code' => null,
            'verified_at' => null,
        ]);

        if (! config('cloudflare.enabled')) {
            return [
                'domain' => $domain,
                'message' => "Domain {$host} saved, but Cloudflare integration is disabled.",
            ];
        }

        try {
            $this->domainSyncService->sync($domain, createWhenMissing: true);

            if ($this->shouldUseAsyncPolling() && $this->domainSyncService->shouldRetry($domain)) {
                SyncPendingCloudflareDomain::dispatch($domain->id);
            }

            return [
                'domain' => $domain,
                'message' => "Domain {$host} added. ".$this->statusMessage($domain),
            ];
        } catch (\Throwable $e) {
            $domain->update([
                'cf_error' => $e->getMessage(),
                'cf_last_checked_at' => Carbon::now(),
            ]);

            logger()->error('cloudflare.hostname.create_failed', [
                'tenant_id' => $domain->tenant_id,
                'domain' => $domain->domain,
                'cf_hostname_id' => $domain->cf_hostname_id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return [
                'domain' => $domain,
                'message' => "Domain saved, but Cloudflare create failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * @throws RuntimeException When validation fails
     */
    private function validate(Tenant $tenant, string $domain): void
    {
        if ($domain === '') {
            throw new RuntimeException('Enter a valid host (no http://, no path).');
        }

        if (str_contains($domain, '://') || str_contains($domain, '/')) {
            throw new RuntimeException('Enter a valid host (no http://, no path).');
        }

        if (filter_var('http://'.$domain, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Enter a valid host (no http://, no path).');
        }

        if ($this->domainService->isCentralDomain($domain)) {
            throw new RuntimeException('Central domains cannot be used as custom tenant domains.');
        }

        if ($this->domainService->isPrimarySubDomain($tenant, $domain)) {
            throw new RuntimeException('Primary tenant domain is already in use.');
        }

        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        if (Domain::query()->where('domain', $domain)->exists()) {
            throw new RuntimeException('This domain is already registered.');
        }
    }

    private function shouldUseAsyncPolling(): bool
    {
        return (bool) config('cloudflare.async_polling', false);
    }

    private function statusMessage(Domain $domain): string
    {
        $parts = [
            'Hostname: '.($domain->cf_hostname_status ?? 'pending'),
            'SSL: '.($domain->cf_ssl_status ?? 'pending'),
        ];

        if ($domain->verified_at) {
            $parts[] = 'Verified and ready to serve traffic.';
        } elseif ($domain->cf_error) {
            $parts[] = 'Last Cloudflare error: '.$domain->cf_error;
        } else {
            $parts[] = 'Still waiting for Cloudflare activation.';
        }

        return implode(' ', $parts);
    }
}
