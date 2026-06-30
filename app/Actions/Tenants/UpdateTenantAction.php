<?php

namespace App\Actions\Tenants;

use App\Models\Tenant;
use App\Services\TenantDomainService;
use Illuminate\Support\Facades\DB;

class UpdateTenantAction
{
    public function __construct(
        private TenantDomainService $domainService,
        private SyncCloudflareDomainAction $syncCloudflareDomainAction,
    ) {}

    public function execute(array $data, Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($data, $tenant) {
            $normalizedDomain = $this->domainService->normalize($data['domain']);

            // Update Tenant
            $tenant->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'description' => $data['description'] ?? null,
            ]);

            // Update Domain
            $domainModel = $tenant->domains()->first();

            if ($domainModel) {
                $domainModel->update(['domain' => $normalizedDomain]);
            } else {
                $domainModel = $tenant->domains()->create(['domain' => $normalizedDomain]);
            }

            // Sync Cloudflare
            $this->syncCloudflareDomainAction->execute($tenant, $domainModel);

            return $tenant;
        });
    }
}
