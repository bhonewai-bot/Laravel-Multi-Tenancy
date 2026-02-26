<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class TenantModuleInstaller
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private TenantModuleRegistry $registry
    ) {}

    public function install($tenant, $module): void
    {
        if (in_array($module->slug, $this->registry->getInstalledModules($tenant), true)) {
            throw new RuntimeException('Module is already installed.');
        }

        $migrationPath = $this->resolveMigrationPath($module);

        if (!$migrationPath) {
            throw new RuntimeException("Module files not found for '{$module->name}'.");
        }

        $phpMigrations = glob($migrationPath . '/*.php') ?: [];
        if (count($phpMigrations) === 0) {
            throw new RuntimeException("No migration files found for '{$module->name}'.");
        }

        $exitCode = Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => $migrationPath,
            '--realpath' => true,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('Migration failed.');
        }

        $this->registry->markInstalled($tenant, $module->slug);
    }

    public function uninstall($tenant, $module): void
    {
        if (!in_array($module->slug, $this->registry->getInstalledModules($tenant), true)) {
            throw new RuntimeException('Module is not installed.');
        }

        $migrationPath = $this->resolveMigrationPath($module);

        if (!$migrationPath) {
            throw new RuntimeException("Module files not found for '{$module->name}'.");
        }

        $exitCode = Artisan::call('migrate:reset', [
            '--database' => 'tenant',
            '--path' => $migrationPath,
            '--realpath' => true,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('Migration failed.');
        }

        $this->registry->markUninstalled($tenant, $module->slug);
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
}
