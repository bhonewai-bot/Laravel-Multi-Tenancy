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

    /**
     * Create a new tenant with its primary domain and Cloudflare sync.
     *
     * Runs without a database transaction because MySQL DDL (CREATE DATABASE in
     * the TenantCreated event pipeline) causes an implicit commit that breaks any
     * transaction guard. For production, enable TENANCY_PROVISIONING_QUEUE so
     * the DDL work moves to a background job.
     *
     * @param  array{tenant_id: string, name: string, email: string, domain: string, description?: string|null}  $data
     */
    public function execute(array $data): Tenant
    {
        $normalizedDomain = $this->domainService->normalize($data['domain']);

        $tenant = Tenant::create([
            'id' => $data['tenant_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'description' => $data['description'] ?? null,
        ]);

        $domainModel = $tenant->domains()->create([
            'domain' => $normalizedDomain,
        ]);

        $this->syncCloudflareDomainAction->execute($tenant, $domainModel);

        return $tenant;
    }
}
