<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Module installation records for this tenant.
     */
    public function installations(): HasMany
    {
        return $this->hasMany(ModuleInstallation::class);
    }

    /**
     * Modules installed for this tenant.
     */
    public function installedModules(): HasManyThrough
    {
        return $this->hasManyThrough(Module::class, ModuleInstallation::class, 'tenant_id', 'id', 'id', 'module_id');
    }

    /**
     * Module operation records for this tenant.
     */
    public function moduleOperations(): HasMany
    {
        return $this->hasMany(ModuleOperation::class);
    }

    /**
     * Return the primary platform subdomain for this tenant.
     */
    public function primaryDomain(): ?Domain
    {
        $centralDomain = config('tenancy.central_domains.0');

        if (! $centralDomain) {
            return null;
        }

        return $this->domains()->where('domain', strtolower($this->id.'.'.$centralDomain))->first();
    }

    /**
     * Determine whether a specific module is installed for this tenant.
     */
    public function isInstalled(string $slug): bool
    {
        return $this->installations()
            ->join('modules', 'module_installations.module_id', '=', 'modules.id')
            ->where('modules.slug', $slug)
            ->exists();
    }

    /**
     * Scope to tenants that have a specific module installed.
     */
    public function scopeWithModule($query, string $slug)
    {
        return $query->whereHas('installedModules', fn ($q) => $q->where('slug', $slug));
    }
}
