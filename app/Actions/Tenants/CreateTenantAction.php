<?php

namespace App\Actions\Tenants;

use App\Models\Tenant;
use App\Services\TenantDomainService;

class CreateTenantAction
{
    public function __construct(
        private TenantDomainService $domainService,
        private SyncCloudflareDomainAction $syncCloudflareDomainAction,
    ) {}

    public function execute(array $data): Tenant
    {
        $normalizedDomain = $this->domainService->normalize($data['domain']);

        // Create Tenant
        $tenant = Tenant::create([
            'id' => $data['tenant_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'description' => $data['description'] ?? null,
        ]);

        // Create Domain
        $domainModel = $tenant->domains()->create([
            'domain' => $normalizedDomain,
        ]);

        // Sync Cloudflare
        $this->syncCloudflareDomainAction->execute($tenant, $domainModel);

        return $tenant;
    }
}
