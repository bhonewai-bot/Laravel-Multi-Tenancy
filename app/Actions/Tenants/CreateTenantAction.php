<?php

namespace App\Actions\Tenants;

use App\Models\Tenant;
use App\Services\TenantDomainService;
use Illuminate\Support\Facades\DB;

class CreateTenantAction
{
    public function __construct(
        private TenantDomainService $domainService,
        private SyncCloudflareDomainAction $syncCloudflareDomainAction,
    ) {}

    public function execute(array $data): Tenant
    {
        $normalizedDomain = $this->domainService->normalize($data['domain']);

        return DB::transaction(function () use ($data, $normalizedDomain) {
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
        });
    }
}
