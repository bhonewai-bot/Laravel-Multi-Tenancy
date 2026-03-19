<?php

namespace App\Support;

use App\Models\Domain;

class HostResolver
{
    /**
     * Determine whether the incoming host belongs to the central app.
     */
    public function isCentralHost(string $host): bool
    {
        $host = $this->normalize($host);

        return in_array($host, $this->centralDomains(), true);
    }

    /**
     * Resolve a tenant-domain record using only local DB state.
     *
     * Request routing must not call Cloudflare. By the time traffic reaches
     * this resolver, Cloudflare sync should have already persisted any status
     * changes onto the domains row.
     */
    public function findTenantDomain(string $host): ?Domain
    {
        $host = $this->normalize($host);

        return Domain::query()
            ->where('domain', $host)
            ->first();
    }

    /**
     * Determine whether the tenant host is already trusted to serve traffic.
     *
     * Pattern 4 policy:
     * - primary platform subdomains are trusted
     * - custom domains are trusted only after local verification state exists
     */
    public function isVerifiedTenantHost(string $host): bool
    {
        $domain = $this->findTenantDomain($host);

        if (! $domain) {
            return false;
        }

        return $domain->verified_at !== null || $this->isPrimarySubDomain($domain);
    }

    /**
     * Alias for request-serving policy checks.
     */
    public function canServeTenantHost(string $host): bool
    {
        return $this->isVerifiedTenantHost($host);
    }

    protected function isPrimarySubDomain(Domain $domain): bool
    {
        foreach ($this->centralDomains() as $centralDomain) {
            if (str_ends_with($domain->domain, '.' . $centralDomain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize host values before comparing them.
     */
    protected function normalize(string $host): string
    {
        return strtolower(rtrim(trim($host), '.'));
    }

    /**
     * Return configured central domains in normalized form.
     *
     * @return array<int, string>
     */
    protected function centralDomains(): array
    {
        return array_map(
            fn (string $domain) => $this->normalize($domain),
            array_filter((array) config('tenancy.central_domains'))
        );
    }
}
