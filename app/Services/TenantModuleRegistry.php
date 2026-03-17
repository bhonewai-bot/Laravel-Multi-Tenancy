<?php

namespace App\Services;

/**
 * Persists tenant module install state and long-running operation status.
 *
 * The registry stores metadata on the central tenant record so the back office can
 * observe tenant module progress without opening the tenant database directly.
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
     * Return the normalized list of modules installed for the tenant.
     *
     * @param  mixed  $tenant
     * @return array
     */
    public function getInstalledModules($tenant): array
    {
        $installed = $tenant->getAttribute('installed_modules') ?? [];

        if (!is_array($installed)) {
            return [];
        }

        return array_values(array_filter($installed, fn ($slug) => is_string($slug) && $slug !== ''));
    }

    /**
     * Mark a module as installed for the tenant.
     *
     * Side effects:
     * - Writes to the central tenant record.
     *
     * @param  mixed  $tenant
     * @param  string  $slug
     * @return void
     */
    public function markInstalled($tenant, string $slug): void
    {
        // Refresh reduces the chance of clobbering module state written by another request or worker.
        $tenant->refresh();
        $installed = $this->getInstalledModules($tenant);
        if (! in_array($slug, $installed, true)) {
            $installed[] = $slug;
        }

        $this->saveInstalledModules($tenant, $installed);
    }

    /**
     * Remove a module from the tenant's installed list.
     *
     * Side effects:
     * - Writes to the central tenant record.
     *
     * @param  mixed  $tenant
     * @param  string  $slug
     * @return void
     */
    public function markUninstalled($tenant, string $slug): void
    {
        $tenant->refresh();
        $installed = array_values(array_filter(
            $this->getInstalledModules($tenant),
            fn (string $item) => $item !== $slug
        ));

        $this->saveInstalledModules($tenant, $installed);
    }

    /**
     * Return all module operation records tracked for the tenant.
     *
     * @param  mixed  $tenant
     * @return array
     */
    public function getModuleOperations($tenant): array
    {
        $operations = $tenant->getAttribute('module_operations') ?? [];

        return is_array($operations) ? $operations : [];
    }

    /**
     * Return the operation state for a single module slug.
     *
     * @param  mixed  $tenant
     * @param  string  $slug
     * @return array|null
     */
    public function getModuleOperation($tenant, string $slug): ?array
    {
        $operations = $this->getModuleOperations($tenant);

        $operation = $operations[$slug] ?? null;
        return is_array($operation) ? $operation : null;
    }

    /**
     * Record that a module operation has been queued.
     *
     * @param  mixed  $tenant
     * @param  string  $slug
     * @param  string  $action
     * @param  string  $message
     * @return void
     */
    public function startModuleOperation($tenant, string $slug, string $action, string $message = ''): void
    {
        $this->upsertModuleOperation($tenant, $slug, $action, self::OP_STATUS_QUEUED, $message);
    }

    /**
     * Record that a queued module operation is actively running.
     *
     * @param  mixed  $tenant
     * @param  string  $slug
     * @param  string  $action
     * @param  string  $message
     * @return void
     */
    public function markModuleOperationRunning($tenant, string $slug, string $action, string $message = ''): void
    {
        $this->upsertModuleOperation($tenant, $slug, $action, self::OP_STATUS_RUNNING, $message);
    }

    /**
     * Record that a module operation completed successfully.
     *
     * @param  mixed  $tenant
     * @param  string  $slug
     * @param  string  $action
     * @param  string  $message
     * @return void
     */
    public function markModuleOperationSucceeded($tenant, string $slug, string $action, string $message): void
    {
        $this->upsertModuleOperation($tenant, $slug, $action, self::OP_STATUS_SUCCESS, $message);
    }

    /**
     * Record that a module operation failed.
     *
     * @param  mixed  $tenant
     * @param  string  $slug
     * @param  string  $action
     * @param  string  $message
     * @return void
     */
    public function markModuleOperationFailed($tenant, string $slug, string $action, string $message): void
    {
        $this->upsertModuleOperation($tenant, $slug, $action, self::OP_STATUS_FAILED, $message);
    }

    /**
     * Remove the tracked operation state for a module after the UI has acknowledged it.
     *
     * Side effects:
     * - Writes to the central tenant record.
     *
     * @param  mixed  $tenant
     * @param  string  $slug
     * @return void
     */
    public function clearModuleOperation($tenant, string $slug): void
    {
        $tenant->refresh();

        $operations = $this->getModuleOperations($tenant);
        unset($operations[$slug]);

        $tenant->setAttribute('module_operations', $operations);
        $tenant->save();
    }

    /**
     * Determine whether the operation status is final.
     *
     * @param  string|null  $status
     * @return bool
     */
    public function isTerminalStatus(?string $status): bool
    {
        return in_array($status, [self::OP_STATUS_SUCCESS, self::OP_STATUS_FAILED], true);
    }

    /**
     * Determine whether the operation is still in progress.
     *
     * @param  string|null  $status
     * @return bool
     */
    public function isProcessingStatus(?string $status): bool
    {
        return in_array($status, [self::OP_STATUS_QUEUED, self::OP_STATUS_RUNNING], true);
    }

    /**
     * Persist the installed module list back to the central tenant record.
     *
     * @param  mixed  $tenant
     * @param  array  $installed
     * @return void
     */
    private function saveInstalledModules($tenant, array $installed): void
    {
        $tenant->setAttribute('installed_modules', $installed);
        $tenant->save();
    }

    /**
     * Upsert a tenant module operation snapshot.
     *
     * WARNING: This is last-write-wins state. Refreshing first reduces stale writes, but
     * concurrent updates for different operations can still overwrite each other if callers
     * bypass the surrounding locking conventions.
     *
     * @param  mixed  $tenant
     * @param  string  $slug
     * @param  string  $action
     * @param  string  $status
     * @param  string  $message
     * @return void
     */
    private function upsertModuleOperation($tenant, string $slug, string $action, string $status, string $message): void
    {
        $tenant->refresh();

        $operations = $this->getModuleOperations($tenant);
        $operations[$slug] = [
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'updated_at' => now()->toDateTimeString(),
        ];

        $tenant->setAttribute('module_operations', $operations);
        $tenant->save();
    }
}
