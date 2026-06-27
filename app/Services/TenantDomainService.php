<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Provides domain-specific rules used across tenant onboarding and request gating.
 *
 * These helpers keep domain normalization and verification logic consistent between
 * central administration flows and tenant-facing domain management.
 */
class TenantDomainService
{
    /**
     * Return configured central domains in normalized form.
     *
     * @return array<int, string>
     */
    public function centralDomains(): array
    {
        return array_map(
            fn (string $domain) => $this->normalize($domain),
            array_filter((array) config('tenancy.central_domains'))
        );
    }

    /**
     * Normalize hostnames before persistence or comparison.
     */
    public function normalize(string $domain): string
    {
        return strtolower(rtrim(trim($domain), '.'));
    }

    /**
     * Generate the TXT verification token used for legacy DNS ownership checks.
     */
    public function makeVerificationCode(): string
    {
        return Str::lower(Str::random(40));
    }

    /**
     * Build the TXT record name expected for legacy DNS verification.
     */
    public function verificationRecordName(string $domain): string
    {
        return '_tenant-verification.'.$this->normalize($domain);
    }

    /**
     * Determine whether a host belongs to the central application.
     *
     * Central domains must never be accepted as tenant custom domains because that
     * would break tenant isolation and create routing ambiguity.
     */
    public function isCentralDomain(string $domain): bool
    {
        $host = $this->normalize($domain);

        $centralDomains = array_map(
            fn (string $item) => strtolower($item),
            array_filter((array) config('tenancy.central_domains'))
        );

        return in_array($host, $centralDomains, true);
    }

    /**
     * Determine whether a host is the tenant's platform-managed primary subdomain.
     */
    public function isPrimarySubDomain(Tenant $tenant, string $domain): bool
    {
        $host = $this->normalize($domain);

        $centralDomains = array_filter((array) config('tenancy.central_domains'));

        foreach ($centralDomains as $centralDomain) {
            if ($host == strtolower("{$tenant->id}.{$centralDomain}")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether a custom domain exists for the tenant and has passed verification.
     *
     * NOTE: The tenant_id predicate is essential to avoid cross-tenant domain leakage.
     */
    public function isVerifiedCustomDomain(Tenant $tenant, string $domain): bool
    {
        $host = $this->normalize($domain);

        $domainModel = Domain::query()
            ->where('tenant_id', $tenant->id)
            ->where('domain', $host)
            ->first();

        if (! $domainModel) {
            return false;
        }

        return $domainModel->verified_at !== null;
    }

    /**
     * Decide whether the current request host is allowed to serve the tenant.
     */
    public function canUseAsTenantDomain(Tenant $tenant, string $domain): bool
    {
        $host = $this->normalize($domain);

        if ($this->isPrimarySubDomain($tenant, $host)) {
            return true;
        }

        return $this->isVerifiedCustomDomain($tenant, $host);
    }

    /**
     * Check the public DNS TXT record for the expected verification token.
     *
     * Side effects:
     * - Performs a DNS lookup.
     */
    public function checkDnsTxtVerification(string $domain, string $verificationCode): bool
    {
        $recordName = $this->verificationRecordName($domain);
        $records = dns_get_record($recordName, DNS_TXT) ?: [];

        foreach ($records as $record) {
            $txt = strtolower(trim($record['txt'] ?? ''));
            if ($txt === strtolower(trim($verificationCode))) {
                return true;
            }
        }

        return false;
    }
}
