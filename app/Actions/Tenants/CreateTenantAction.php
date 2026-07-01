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

        DB::beginTransaction();

        try {
            $tenant = Tenant::create([
                'id' => $data['tenant_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'description' => $data['description'] ?? null,
            ]);

            // MySQL DDL (CREATE DATABASE in TenantCreated event) causes an
            // implicit commit, so the transaction guard below tolerates that.
            $domainModel = $tenant->domains()->create([
                'domain' => $normalizedDomain,
            ]);

            $this->syncCloudflareDomainAction->execute($tenant, $domainModel);

            $this->commitIfActive();

            return $tenant;
        } catch (\Throwable $e) {
            $this->rollbackIfActive();

            throw $e;
        }
    }

    /**
     * Commit the current transaction if one is still active.
     *
     * MySQL DDL statements (e.g., CREATE DATABASE during tenant provisioning)
     * cause an implicit commit, so the transaction guard may already be closed.
     */
    private function commitIfActive(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::commit();
        }
    }

    /**
     * Roll back the current transaction if one is still active.
     */
    private function rollbackIfActive(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }
}
