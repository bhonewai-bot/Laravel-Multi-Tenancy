<?php

namespace App\Services;

use BadMethodCallException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class TenantModuleInstaller
{
    public const RESULT_INSTALLED = 'installed';
    public const RESULT_ALREADY_INSTALLED = 'already_installed';
    public const RESULT_UNINSTALLED = 'uninstalled';
    public const RESULT_ALREADY_UNINSTALLED = 'already_uninstalled';

    public function __construct(
        private TenantModuleRegistry $registry
    ) {}

    public function install($tenant, $module): string
    {
        return $this->withOperationLock($tenant, $module, function () use ($tenant, $module): string {
            if (in_array($module->slug, $this->registry->getInstalledModules($tenant), true)) {
                return self::RESULT_ALREADY_INSTALLED;
            }

            $migrationPath = $this->resolveMigrationPath($module);

            if (!$migrationPath) {
                throw new RuntimeException("Module files not found for '{$module->name}'.");
            }

            $phpMigrations = glob($migrationPath . '/*.php') ?: [];
            if (count($phpMigrations) === 0) {
                throw new RuntimeException("No migration files found for '{$module->name}'.");
            }

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

            // Update install state only after migration/seed succeeds.
            $this->registry->markInstalled($tenant, $module->slug);

            return self::RESULT_INSTALLED;
        });
    }

    public function uninstall($tenant, $module): string
    {
        return $this->withOperationLock($tenant, $module, function () use ($tenant, $module): string {
            if (!in_array($module->slug, $this->registry->getInstalledModules($tenant), true)) {
                return self::RESULT_ALREADY_UNINSTALLED;
            }

            $migrationPath = $this->resolveMigrationPath($module);

            if (!$migrationPath) {
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

    private function runSeederIfExists($module): void
    {
        $seederClass = $this->resolveSeederClass($module);

        if (!$seederClass) {
            return;
        }

        $seedExitCode = Artisan::call('db:seed', [
            '--database' => 'tenant',
            '--class' => $seederClass,
            '--force' => true
        ]);

        if ($seedExitCode !== 0) {
            throw new RuntimeException('Seeder failed.');
        }
    }

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

    private function withOperationLock($tenant, $module, callable $operation): string
    {
        try {
            $lock = Cache::lock($this->lockKey($tenant, $module), 30);
        } catch (BadMethodCallException) {
            return $operation();
        }

        if (!$lock->get()) {
            throw new RuntimeException('Another module operation is running. Please retry in a moment.');
        }

        try {
            return $operation();
        } finally {
            $lock->release();
        }
    }

    private function lockKey($tenant, $module): string
    {
        $tenantId = $tenant->getTenantKey() ?? $tenant->id ?? 'unknown';

        return "tenant:module-operation:{$tenantId}:{$module->slug}";
    }
}
