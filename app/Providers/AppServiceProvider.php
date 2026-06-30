<?php

namespace App\Providers;

use App\Http\Middleware\RejectInvalidTenantHost;
use App\Models\ModuleRequest;
use App\Models\Role;
use App\Models\User;
use App\Policies\ModuleRequestPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Services\CentralAdminService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * Registers application-wide policies and bootstraps central-only startup services.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register container bindings for the application.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap policies and ensure the configured central admin exists.
     *
     * Side effects:
     * - Registers authorization policies.
     * - May write to the central users table through CentralAdminService.
     */
    public function boot(): void
    {
        Gate::define('access-central-admin', function (User $user) {
            return $user->email === config('auth.central_admin.email');
        });

        Gate::policy(ModuleRequest::class, ModuleRequestPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        // This runs after policy registration so the admin bootstrap can access guarded routes immediately.
        app(CentralAdminService::class)->ensureConfiguredSuperAdminExists();

        // Why livewire here?
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)->middleware([
                'web',
                RejectInvalidTenantHost::class,
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
            ]);
        });
    }
}
