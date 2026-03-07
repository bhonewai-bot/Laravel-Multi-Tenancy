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

class InstallTenantModule implements ShouldQueue
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
            logger()->warning('InstallTenantModule skipped: missing tenant/module.', [
                'tenant_id' => $this->tenantId,
                'module_id' => $this->moduleId,
            ]);
            return;
        }

        $registry->markModuleOperationRunning($tenant, $module->slug, 'install', "Installing '{$module->name}'...");

        Tenancy::initialize($tenant);

        try {
            $result = $installer->install($tenant, $module);

            $message = $result === TenantModuleInstaller::RESULT_ALREADY_INSTALLED
                ? "Module '{$module->name}' is already installed."
                : "Module '{$module->name}' installed successfully.";

            $registry->markModuleOperationSucceeded($tenant, $module->slug, 'install', $message);

            logger()->info('InstallTenantModule completed.', [
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
                'install',
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