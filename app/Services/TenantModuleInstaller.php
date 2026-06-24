<?php

namespace App\Services;

use BadMethodCallException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Applies tenant module migrations and seeders inside the active tenant context.
 *
 * This service is used by queued jobs after tenancy has been initialized. All
 * persistence therefore targets the tenant database, not the central database.
 * WARNING: Calling this service outside the correct tenant context risks cross-tenant writes.
 */
class TenantModuleInstaller
{
    public const RESULT_INSTALLED = 'installed';

    public const RESULT_ALREADY_INSTALLED = 'already_installed';

    public const RESULT_UNINSTALLED = 'uninstalled';

    public const RESULT_ALREADY_UNINSTALLED = 'already_uninstalled';

    public function __construct(
        private TenantModuleRegistry $registry
    ) {}

    /**
     * Install a module for a tenant by running its tenant-scoped migrations and optional seeder.
     *
     * Side effects:
     * - Executes Artisan migration and seeding commands against the tenant connection.
     * - Updates the tenant's installed module registry in the central tenant record.
     *
     * @param  mixed  $tenant
     * @param  mixed  $module
     */
    public function install($tenant, $module): string
    {
        return $this->withOperationLock($tenant, $module, function () use ($tenant, $module): string {
            if (in_array($module->slug, $this->registry->getInstalledModules($tenant), true)) {
                return self::RESULT_ALREADY_INSTALLED;
            }

            // Resolve module files before mutating tenant state so missing assets fail cleanly.
            $migrationPath = $this->resolveMigrationPath($module);

            if (! $migrationPath) {
                throw new RuntimeException("Module files not found for '{$module->name}'.");
            }

            $phpMigrations = glob($migrationPath.'/*.php') ?: [];
            if (count($phpMigrations) === 0) {
                throw new RuntimeException("No migration files found for '{$module->name}'.");
            }

            // Tenant connection is expected to be active before this point; otherwise migrations can hit the wrong database.
            $migrateExitCode = Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => $migrationPath,
                '--realpath' => true,
                '--force' => true,
            ]);

            if ($migrateExitCode !== 0) {
                throw new RuntimeException('Migration failed.');
            }

            $this->runSeederIfExists($module);

            // Persist the installed flag only after the database shape is ready for tenant traffic.
            $this->registry->markInstalled($tenant, $module->slug);

            return self::RESULT_INSTALLED;
        });
    }

    /**
     * Uninstall a tenant module by rolling back its module-specific migrations.
     *
     * Side effects:
     * - Executes Artisan rollback commands against the tenant connection.
     * - Updates the tenant's installed module registry in the central tenant record.
     *
     * @param  mixed  $tenant
     * @param  mixed  $module
     */
    public function uninstall($tenant, $module): string
    {
        return $this->withOperationLock($tenant, $module, function () use ($tenant, $module): string {
            if (! in_array($module->slug, $this->registry->getInstalledModules($tenant), true)) {
                return self::RESULT_ALREADY_UNINSTALLED;
            }

            $migrationPath = $this->resolveMigrationPath($module);

            if (! $migrationPath) {
                throw new RuntimeException("Module files not found for '{$module->name}'.");
            }

            $resetExitCode = Artisan::call('migrate:reset', [
                '--database' => 'tenant',
                '--path' => $migrationPath,
                '--realpath' => true,
                '--force' => true,
            ]);

            if ($resetExitCode !== 0) {
                throw new RuntimeException('Migration rollback failed.');
            }

            $this->registry->markUninstalled($tenant, $module->slug);

            return self::RESULT_UNINSTALLED;
        });
    }

    /**
     * Resolve the filesystem path containing the module's migration files.
     *
     * @param  mixed  $module
     */
    private function resolveMigrationPath($module): ?string
    {
        $studlyFromName = Str::studly($module->name);
        $studlyFromSlug = Str::studly($module->slug);

        $candidates = [
            base_path("Modules/{$studlyFromName}/database/migrations"),
            base_path("Modules/{$studlyFromName}/Database/Migrations"),
            base_path("Modules/{$studlyFromSlug}/database/migrations"),
            base_path("Modules/{$studlyFromSlug}/Database/Migrations"),
        ];

        foreach ($candidates as $path) {
            if (File::isDirectory($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Run the module seeder when the module defines one.
     *
     * Side effects:
     * - Executes an Artisan seeding command against the tenant connection.
     *
     * @param  mixed  $module
     */
    private function runSeederIfExists($module): void
    {
        $seederClass = $this->resolveSeederClass($module);

        if (! $seederClass) {
            return;
        }

        $seedExitCode = Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => $seederClass,
            '--force' => true,
        ]);

        if ($seedExitCode !== 0) {
            throw new RuntimeException('Seeder failed.');
        }
    }

    /**
     * Resolve the module seeder class using both display name and slug conventions.
     *
     * @param  mixed  $module
     */
    private function resolveSeederClass($module): ?string
    {
        $studlyFromName = Str::studly($module->name);
        $studlyFromSlug = Str::studly($module->slug);

        $candidates = [
            "Modules\\{$studlyFromName}\\Database\\Seeders\\{$studlyFromName}DatabaseSeeder",
            "Modules\\{$studlyFromSlug}\\Database\\Seeders\\{$studlyFromSlug}DatabaseSeeder",
        ];

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Serialize module operations for a tenant to reduce concurrent migration conflicts.
     *
     * WARNING: The lock only coordinates callers using the same cache backend. A non-locking
     * cache driver falls back to direct execution, so operational concurrency guarantees weaken.
     *
     * @param  mixed  $tenant
     * @param  mixed  $module
     */
    private function withOperationLock($tenant, $module, callable $operation): string
    {
        try {
            $lock = Cache::lock($this->lockKey($tenant, $module), 30);
        } catch (BadMethodCallException) {
            return $operation();
        }

        // Module migrations are not transaction-safe across all drivers, so a coarse lock is used instead.
        if (! $lock->get()) {
            throw new RuntimeException('Another module operation is running. Please retry in a moment.');
        }

        try {
            return $operation();
        } finally {
            $lock->release();
        }
    }

    /**
     * Build a tenant-scoped cache lock key for module operations.
     *
     * @param  mixed  $tenant
     * @param  mixed  $module
     */
    private function lockKey($tenant, $module): string
    {
        $tenantId = $tenant->getTenantKey() ?? $tenant->id ?? 'unknown';

        return "tenant:module-operation:{$tenantId}:{$module->slug}";
    }
}
