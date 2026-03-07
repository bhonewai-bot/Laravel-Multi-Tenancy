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

class UninstallTenantModule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public string $tenantId,
        public int $moduleId
    ) {}

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(TenantModuleInstaller $installer, TenantModuleRegistry $registry): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        $module = Module::query()->find($this->moduleId);

        if (! $tenant || ! $module) {
            logger()->warning('UninstallTenantModule skipped: missing tenant/module.', [
                'tenant_id' => $this->tenantId,
                'module_id' => $this->moduleId,
            ]);
            return;
        }

        $registry->markModuleOperationRunning($tenant, $module->slug, 'uninstall', "Uninstalling '{$module->name}'...");

        Tenancy::initialize($tenant);

        try {
            $result = $installer->uninstall($tenant, $module);

            $message = $result === TenantModuleInstaller::RESULT_ALREADY_UNINSTALLED
                ? "Module '{$module->name}' is already uninstalled."
                : "Module '{$module->name}' uninstalled successfully.";

            $registry->markModuleOperationSucceeded($tenant, $module->slug, 'uninstall', $message);

            logger()->info('UninstallTenantModule completed.', [
                'tenant_id' => $tenant->id,
                'module' => $module->slug,
                'result' => $result,
            ]);
        } finally {
            Tenancy::end();
        }
    }

    public function failed(Throwable $exception): void
    {
        $tenant = Tenant::query()->find($this->tenantId);
        $module = Module::query()->find($this->moduleId);

        if ($tenant && $module) {
            app(TenantModuleRegistry::class)->markModuleOperationFailed(
                $tenant,
                $module->slug,
                'uninstall',
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