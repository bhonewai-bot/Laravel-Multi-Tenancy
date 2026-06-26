<?php

namespace App\Actions\Tenants;

use App\Models\Domain;
use App\Models\Tenant;
use App\Services\DomainCloudflareSyncService;
use App\Services\TenantDomainService;
use RuntimeException;

/**
 * Verify a tenant domain via Cloudflare status polling or legacy DNS TXT records.
 *
 * Side effects:
 * - May call Cloudflare or perform DNS lookups.
 * - Writes verification metadata to the central domains table.
 */
class VerifyDomainAction
{
    public function __construct(
        private TenantDomainService $domainService,
        private DomainCloudflareSyncService $domainSyncService,
    ) {}

    /**
     * @return array{status: 'success'|'warning'|'error', message: string}
     */
    public function execute(Tenant $tenant, Domain $domain): array
    {
        if ($domain->tenant_id !== $tenant->id) {
            throw new RuntimeException('Domain does not belong to this tenant.');
        }

        // Cloudflare-managed domains use status polling.
        if ($domain->cf_hostname_id) {
            return $this->verifyViaCloudflare($domain);
        }

        return $this->verifyViaDnsTxt($tenant, $domain);
    }

    /**
     * @return array{status: 'success'|'warning'|'error', message: string}
     */
    private function verifyViaCloudflare(Domain $domain): array
    {
        try {
            $this->domainSyncService->sync($domain, createWhenMissing: true);

            if ($this->shouldUseAsyncPolling() && $this->domainSyncService->shouldRetry($domain)) {
                \App\Jobs\SyncPendingCloudflareDomain::dispatch($domain->id);
            }

            if ($domain->verified_at) {
                return ['status' => 'success', 'message' => 'Domain is active and SSL is live.'];
            }

            return ['status' => 'warning', 'message' => $this->statusMessage($domain)];
        } catch (\Throwable $e) {
            $domain->update([
                'cf_error' => $e->getMessage(),
                'cf_last_checked_at' => now(),
            ]);

            logger()->error('cloudflare.hostname.status_check_failed', [
                'tenant_id' => $domain->tenant_id,
                'domain' => $domain->domain,
                'cf_hostname_id' => $domain->cf_hostname_id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => "Status check failed: {$e->getMessage()}"];
        }
    }

    /**
     * @return array{status: 'success'|'warning'|'error', message: string}
     */
    private function verifyViaDnsTxt(Tenant $tenant, Domain $domain): array
    {
        if ($this->domainService->isPrimarySubDomain($tenant, $domain->domain)) {
            return ['status' => 'success', 'message' => 'Primary tenant subdomain is trusted automatically.'];
        }

        // Generate the TXT token lazily so retries use a stable expected DNS record.
        if (! $domain->verification_code) {
            $domain->verification_code = $this->domainService->makeVerificationCode();
            $domain->verified_at = null;
            $domain->save();
        }

        $ok = $this->domainService->checkDnsTxtVerification($domain->domain, $domain->verification_code);

        if (! $ok) {
            $record = $this->domainService->verificationRecordName($domain->domain);

            return ['status' => 'error', 'message' => "DNS TXT not matched. Expected: {$record} = {$domain->verification_code}"];
        }

        $domain->verified_at = now();
        $domain->save();

        return ['status' => 'success', 'message' => "Domain {$domain->domain} verified successfully."];
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
