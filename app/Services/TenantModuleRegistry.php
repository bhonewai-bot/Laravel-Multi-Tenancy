<?php

namespace App\Services;

class TenantModuleRegistry
{
    public const ACTION_INSTALL = 'install';
    public const ACTION_UNINSTALL = 'uninstall';

    public const OP_STATUS_QUEUED = 'queued';
    public const OP_STATUS_RUNNING = 'running';
    public const OP_STATUS_SUCCESS = 'success';
    public const OP_STATUS_FAILED = 'failed';

    public function getInstalledModules($tenant): array
    {
        $installed = $tenant->getAttribute('installed_modules') ?? [];

        if (!is_array($installed)) {
            return [];
        }

        return array_values(array_filter($installed, fn ($slug) => is_string($slug) && $slug !== ''));
    }

    public function markInstalled($tenant, string $slug): void
    {
        $tenant->refresh();
        $installed = $this->getInstalledModules($tenant);
        if (! in_array($slug, $installed, true)) {
            $installed[] = $slug;
        }

        $this->saveInstalledModules($tenant, $installed);
    }

    public function markUninstalled($tenant, string $slug): void
    {
        $tenant->refresh();
        $installed = array_values(array_filter(
            $this->getInstalledModules($tenant),
            fn (string $item) => $item !== $slug
        ));

        $this->saveInstalledModules($tenant, $installed);
    }

    public function getModuleOperations($tenant): array
    {
        $operations = $tenant->getAttribute('module_operations') ?? [];

        return is_array($operations) ? $operations : [];
    }

    public function getModuleOperation($tenant, string $slug): ?array
    {
        $operations = $this->getModuleOperations($tenant);

        $operation = $operations[$slug] ?? null;
        return is_array($operation) ? $operation : null;
    }

    public function startModuleOperation($tenant, string $slug, string $action, string $message = ''): void
    {
        $this->upsertModuleOperation($tenant, $slug, $action, self::OP_STATUS_QUEUED, $message);
    }

    public function markModuleOperationRunning($tenant, string $slug, string $action, string $message = ''): void
    {
        $this->upsertModuleOperation($tenant, $slug, $action, self::OP_STATUS_RUNNING, $message);
    }

    public function markModuleOperationSucceeded($tenant, string $slug, string $action, string $message): void
    {
        $this->upsertModuleOperation($tenant, $slug, $action, self::OP_STATUS_SUCCESS, $message);
    }

    public function markModuleOperationFailed($tenant, string $slug, string $action, string $message): void
    {
        $this->upsertModuleOperation($tenant, $slug, $action, self::OP_STATUS_FAILED, $message);
    }

    public function clearModuleOperation($tenant, string $slug): void
    {
        $tenant->refresh();

        $operations = $this->getModuleOperations($tenant);
        unset($operations[$slug]);

        $tenant->setAttribute('module_operations', $operations);
        $tenant->save();
    }

    public function isTerminalStatus(?string $status): bool
    {
        return in_array($status, [self::OP_STATUS_SUCCESS, self::OP_STATUS_FAILED], true);
    }

    public function isProcessingStatus(?string $status): bool
    {
        return in_array($status, [self::OP_STATUS_QUEUED, self::OP_STATUS_RUNNING], true);
    }

    private function saveInstalledModules($tenant, array $installed): void
    {
        $tenant->setAttribute('installed_modules', $installed);
        $tenant->save();
    }

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
