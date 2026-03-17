<?php

namespace App\Jobs;

use App\Models\Module;
use App\Models\Tenant;
use App\Services\TenantModuleInstaller;
use App\Services\TenantModuleRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stancl\Tenancy\Facades\Tenancy;
use Throwable;

/**
 * Executes tenant module installation asynchronously.
 *
 * The job rehydrates the tenant and module from the central database, initializes
 * tenancy for isolation, and then delegates the actual migration/seeding flow to
 * TenantModuleInstaller.
 */
class InstallTenantModule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public string $tenantId,
        public int $moduleId
    ) {}

    /**
     * Return the retry schedule for transient queue failures.
     *
     * @return array
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    /**
     * Install the requested module for the tenant inside the tenant database context.
     *
     * Side effects:
     * - Reads central tenant/module metadata.
     * - Writes module operation state to the central tenant record.
     * - Runs tenant migrations and seeders through TenantModuleInstaller.
     *
     * @param  TenantModuleInstaller  $installer
     * @param  TenantModuleRegistry  $registry
     * @return void
     */
    public function handle(TenantModuleInstaller $installer, TenantModuleRegistry $registry): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        $module = Module::query()->find($this->moduleId);

        // Missing records should not be retried indefinitely because the payload is no longer actionable.
        if (! $tenant || ! $module) {
            logger()->warning('InstallTenantModule skipped: missing tenant/module.', [
                'tenant_id' => $this->tenantId,
                'module_id' => $this->moduleId,
            ]);
            return;
        }

        // The UI polls this central state while the tenant-scoped work is running in the background.
        $registry->markModuleOperationRunning(
            $tenant,
            $module->slug,
            TenantModuleRegistry::ACTION_INSTALL,
            "Installing '{$module->name}'..."
        );

        // WARNING: All writes after this point target the tenant connection until tenancy ends.
        Tenancy::initialize($tenant);

        try {
            $result = $installer->install($tenant, $module);

            $message = $result === TenantModuleInstaller::RESULT_ALREADY_INSTALLED
                ? "Module '{$module->name}' is already installed."
                : "Module '{$module->name}' installed successfully.";

            $registry->markModuleOperationSucceeded(
                $tenant,
                $module->slug,
                TenantModuleRegistry::ACTION_INSTALL,
                $message
            );

            logger()->info('InstallTenantModule completed.', [
                'tenant_id' => $tenant->id,
                'module' => $module->slug,
                'result' => $result,
            ]);
        } finally {
            Tenancy::end();
        }
    }

    /**
     * Mark the operation as failed after Laravel exhausts queue retries.
     *
     * Side effects:
     * - Writes failure state to the central tenant record.
     * - Emits an error log for operations monitoring.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        $module = Module::query()->find($this->moduleId);

        if ($tenant && $module) {
            app(TenantModuleRegistry::class)->markModuleOperationFailed(
                $tenant,
                $module->slug,
                TenantModuleRegistry::ACTION_INSTALL,
                "Install failed for '{$module->name}'. Check logs."
            );
        }

        logger()->error('InstallTenantModule failed.', [
            'tenant_id' => $this->tenantId,
            'module_id' => $this->moduleId,
            'job' => static::class,
            'error' => $exception->getMessage(),
        ]);
    }
}
