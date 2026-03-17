<?php

namespace App\Providers;

use App\Models\ModuleRequest;
use App\Models\Role;
use App\Models\User;
use App\Policies\ModuleRequestPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Services\CentralAdminService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Registers application-wide policies and bootstraps central-only startup services.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register container bindings for the application.
     *
     * @return void
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
     *
     * @return void
     */
    public function boot(): void
    {
        Gate::policy(ModuleRequest::class, ModuleRequestPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        // This runs after policy registration so the admin bootstrap can access guarded routes immediately.
        app(CentralAdminService::class)->ensureConfiguredSuperAdminExists();
    }
}
