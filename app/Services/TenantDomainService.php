<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Support\Str;

class TenantDomainService
{
    public function __construct()
    {
        //
    }

    public function normalize(string $domain): string
    {
        return strtolower(rtrim(trim($domain), '.'));
    }

    public function makeVerificationCode(): string
    {
        return Str::lower(Str::random(40));
    }

    public function verificationRecordName(string $domain): string
    {
        return '_tenant-verification.' . $this->normalize($domain);
    }

    public function isCentralDomain(string $domain): bool
    {
        $host = $this->normalize($domain);

        $centralDomains = array_map(
            fn (string $item) => strtolower($item),
            array_filter((array) config('tenancy.central_domains'))
        );

        return in_array($host, $centralDomains, true);
    }

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

    public function isVerifiedCustomDomain(Tenant $tenant, string $domain): bool
    {
        $host = $this->normalize($domain);

        $domainModel = Domain::query()
            ->where('tenant_id', $tenant->id)
            ->where('domain', $host)
            ->first();

        if (!$domainModel) {
            return false;
        }

        return $domainModel->verified_at !== null;
    }

    public function canUseAsTenantDomain(Tenant $tenant, string $domain): bool
    {
        $host = $this->normalize($domain);

        if ($this->isPrimarySubDomain($tenant, $host)) {
            return true;
        }

        return $this->isVerifiedCustomDomain($tenant, $host);
    }

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
