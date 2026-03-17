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
 * Executes tenant module uninstallation asynchronously.
 *
 * The job restores the tenant context before rollback so module removal only affects
 * the targeted tenant database.
 */
class UninstallTenantModule implements ShouldQueue
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
     * Uninstall the requested module for the tenant inside the tenant database context.
     *
     * Side effects:
     * - Reads central tenant/module metadata.
     * - Writes module operation state to the central tenant record.
     * - Runs tenant rollback commands through TenantModuleInstaller.
     *
     * @param  TenantModuleInstaller  $installer
     * @param  TenantModuleRegistry  $registry
     * @return void
     */
    public function handle(TenantModuleInstaller $installer, TenantModuleRegistry $registry): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        $module = Module::query()->find($this->moduleId);

        // Missing records usually mean the central source of truth changed after dispatch.
        if (! $tenant || ! $module) {
            logger()->warning('UninstallTenantModule skipped: missing tenant/module.', [
                'tenant_id' => $this->tenantId,
                'module_id' => $this->moduleId,
            ]);
            return;
        }

        $registry->markModuleOperationRunning(
            $tenant,
            $module->slug,
            TenantModuleRegistry::ACTION_UNINSTALL,
            "Uninstalling '{$module->name}'..."
        );

        // WARNING: All writes after this point target the tenant connection until tenancy ends.
        Tenancy::initialize($tenant);

        try {
            $result = $installer->uninstall($tenant, $module);

            $message = $result === TenantModuleInstaller::RESULT_ALREADY_UNINSTALLED
                ? "Module '{$module->name}' is already uninstalled."
                : "Module '{$module->name}' uninstalled successfully.";

            $registry->markModuleOperationSucceeded(
                $tenant,
                $module->slug,
                TenantModuleRegistry::ACTION_UNINSTALL,
                $message
            );

            logger()->info('UninstallTenantModule completed.', [
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
                TenantModuleRegistry::ACTION_UNINSTALL,
                "Uninstall failed for '{$module->name}'. Check logs."
            );
        }

        logger()->error('UninstallTenantModule failed.', [
            'tenant_id' => $this->tenantId,
            'module_id' => $this->moduleId,
            'job' => static::class,
            'error' => $exception->getMessage(),
        ]);
    }
}
