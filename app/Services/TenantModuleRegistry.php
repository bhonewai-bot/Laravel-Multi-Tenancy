<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleInstallation;
use App\Models\ModuleOperation;
use Illuminate\Support\Facades\DB;

/**
 * Persists tenant module install state and long-running operation status.
 *
 * All reads and writes go through dedicated database tables with atomic transactions,
 * replacing the previous JSON blob read-modify-write on the central tenant record.
 */
class TenantModuleRegistry
{
    public const ACTION_INSTALL = 'install';

    public const ACTION_UNINSTALL = 'uninstall';

    public const OP_STATUS_QUEUED = 'queued';

    public const OP_STATUS_RUNNING = 'running';

    public const OP_STATUS_SUCCESS = 'success';

    public const OP_STATUS_FAILED = 'failed';

    /**
     * Return the normalized list of module slugs installed for the tenant.
     *
     * @param  mixed  $tenant
     */
    public function getInstalledModules($tenant): array
    {
        return ModuleInstallation::query()
            ->where('tenant_id', $tenant->getTenantKey())
            ->join('modules', 'module_installations.module_id', '=', 'modules.id')
            ->pluck('modules.slug')
            ->all();
    }

    /**
     * Mark a module as installed for the tenant.
     *
     * Side effects:
     * - Writes to the module_installations table.
     *
     * @param  mixed  $tenant
     */
    public function markInstalled($tenant, string $slug): void
    {
        $module = Module::where('slug', $slug)->first();

        if (! $module) {
            return;
        }

        DB::transaction(function () use ($tenant, $module) {
            ModuleInstallation::updateOrCreate(
                ['tenant_id' => $tenant->getTenantKey(), 'module_id' => $module->id],
                ['installed_at' => now()]
            );
        });
    }

    /**
     * Remove a module from the tenant's installed list.
     *
     * Side effects:
     * - Deletes from the module_installations table.
     *
     * @param  mixed  $tenant
     */
    public function markUninstalled($tenant, string $slug): void
    {
        $module = Module::where('slug', $slug)->first();

        if (! $module) {
            return;
        }

        DB::transaction(function () use ($tenant, $module) {
            ModuleInstallation::query()
                ->where('tenant_id', $tenant->getTenantKey())
                ->where('module_id', $module->id)
                ->delete();
        });
    }

    /**
     * Return all module operation records tracked for the tenant, keyed by slug.
     *
     * @param  mixed  $tenant
     * @return array<string, array{action: string, status: string, message: string, updated_at: string}>
     */
    public function getModuleOperations($tenant): array
    {
        return ModuleOperation::query()
            ->where('tenant_id', $tenant->getTenantKey())
            ->get()
            ->mapWithKeys(fn (ModuleOperation $op) => [
                $op->module_slug => [
                    'action' => $op->action,
                    'status' => $op->status,
                    'message' => $op->message,
                    'updated_at' => $op->updated_at?->toDateTimeString() ?? '',
                ],
            ])
            ->all();
    }

    /**
     * Return the operation state for a single module slug.
     *
     * @param  mixed  $tenant
     */
    public function getModuleOperation($tenant, string $slug): ?array
    {
        $op = ModuleOperation::query()
            ->where('tenant_id', $tenant->getTenantKey())
            ->where('module_slug', $slug)
            ->first();

        if (! $op) {
            return null;
        }

        return [
            'action' => $op->action,
            'status' => $op->status,
            'message' => $op->message,
            'updated_at' => $op->updated_at?->toDateTimeString() ?? '',
        ];
    }

    /**
     * Record that a module operation has been queued.
     *
     * @param  mixed  $tenant
     */
    public function startModuleOperation($tenant, string $slug, string $action, string $message = ''): void
    {
        $this->upsertModuleOperation($tenant, $slug, $action, self::OP_STATUS_QUEUED, $message);
    }

    /**
     * Record that a queued module operation is actively running.
     *
     * @param  mixed  $tenant
     */
    public function markModuleOperationRunning($tenant, string $slug, string $action, string $message = ''): void
    {
        $this->upsertModuleOperation($tenant, $slug, $action, self::OP_STATUS_RUNNING, $message);
    }

    /**
     * Record that a module operation completed successfully.
     *
     * @param  mixed  $tenant
     */
    public function markModuleOperationSucceeded($tenant, string $slug, string $action, string $message): void
    {
        $this->upsertModuleOperation($tenant, $slug, $action, self::OP_STATUS_SUCCESS, $message);
    }

    /**
     * Record that a module operation failed.
     *
     * @param  mixed  $tenant
     */
    public function markModuleOperationFailed($tenant, string $slug, string $action, string $message): void
    {
        $this->upsertModuleOperation($tenant, $slug, $action, self::OP_STATUS_FAILED, $message);
    }

    /**
     * Remove the tracked operation state for a module after the UI has acknowledged it.
     *
     * Side effects:
     * - Deletes from the module_operations table.
     *
     * @param  mixed  $tenant
     */
    public function clearModuleOperation($tenant, string $slug): void
    {
        DB::transaction(function () use ($tenant, $slug) {
            ModuleOperation::query()
                ->where('tenant_id', $tenant->getTenantKey())
                ->where('module_slug', $slug)
                ->delete();
        });
    }

    /**
     * Migrate legacy JSON blob data into the dedicated module tables.
     *
     * Called by the data migration. Reads installed_modules and module_operations
     * from the tenant data column and inserts them into module_installations and module_operations.
     */
    public static function migrateFromJsonBlobs(): void
    {
        $tenants = DB::table('tenants')->select('id', 'data')->get();

        foreach ($tenants as $tenant) {
            $data = json_decode((string) $tenant->data, true);

            if (! is_array($data)) {
                continue;
            }

            $installed = $data['installed_modules'] ?? [];
            if (is_array($installed)) {
                $moduleIds = DB::table('modules')
                    ->whereIn('slug', $installed)
                    ->pluck('id', 'slug');

                foreach ($moduleIds as $slug => $moduleId) {
                    DB::table('module_installations')->updateOrInsert(
                        ['tenant_id' => $tenant->id, 'module_id' => $moduleId],
                        ['installed_at' => now(), 'updated_at' => now()]
                    );
                }
            }

            $operations = $data['module_operations'] ?? [];
            if (is_array($operations)) {
                foreach ($operations as $slug => $operation) {
                    if (! is_array($operation)) {
                        continue;
                    }

                    DB::table('module_operations')->updateOrInsert(
                        ['tenant_id' => $tenant->id, 'module_slug' => $slug],
                        [
                            'action' => $operation['action'] ?? 'install',
                            'status' => $operation['status'] ?? 'unknown',
                            'message' => $operation['message'] ?? '',
                            'updated_at' => $operation['updated_at'] ?? now(),
                            'created_at' => $operation['updated_at'] ?? now(),
                        ]
                    );
                }
            }
        }
    }

    /**
     * Determine whether the operation status is final.
     */
    public function isTerminalStatus(?string $status): bool
    {
        return in_array($status, [self::OP_STATUS_SUCCESS, self::OP_STATUS_FAILED], true);
    }

    /**
     * Determine whether the operation is still in progress.
     */
    public function isProcessingStatus(?string $status): bool
    {
        return in_array($status, [self::OP_STATUS_QUEUED, self::OP_STATUS_RUNNING], true);
    }

    /**
     * Upsert a tenant module operation snapshot atomically.
     *
     * @param  mixed  $tenant
     */
    private function upsertModuleOperation($tenant, string $slug, string $action, string $status, string $message): void
    {
        DB::transaction(function () use ($tenant, $slug, $action, $status, $message) {
            ModuleOperation::updateOrCreate(
                ['tenant_id' => $tenant->getTenantKey(), 'module_slug' => $slug],
                ['action' => $action, 'status' => $status, 'message' => $message]
            );
        });
    }
}
