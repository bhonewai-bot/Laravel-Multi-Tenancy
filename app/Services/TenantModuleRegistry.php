<?php

namespace App\Services;

class TenantModuleRegistry
{
    public function getInstalledModules($tenant): array
    {
        $installedModules = $tenant->getAttribute('installed_modules') ?? [];

        if (!is_array($installedModules)) {
            return [];
        }

        return array_map(
            fn ($slug) => $slug,
            $installedModules
        );
    }

    public function markInstalled($tenant, string $slug): void
    {
        $installedModules = $this->getInstalledModules($tenant);

        if (! in_array($slug, $installedModules, true)) {
            $installedModules[] = $slug;
        }

        $this->persiste($tenant, $installedModules);
    }

    public function markUninstalled($tenant, $slug): void
    {
        $installedModules = array_values(array_filter(
            $this->getInstalledModules($tenant),
            fn (string $item) => $item !==  $slug
        ));

        $this->persiste($tenant, $installedModules);
    }

    private function persiste($tenant, array $installedModules): void
    {
        $tenant->setAttribute('installed_modules', $installedModules);
        $tenant->save();
    }
}
