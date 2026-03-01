<?php

use App\Http\Middleware\EnsureModuleInstalled;
use App\Http\Middleware\EnsureTenantPermission;
use App\Http\Middleware\EnsureTenantRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function () {
            $centralDomains = config('tenancy.central_domains');

            foreach ($centralDomains as $domain) {
                Route::middleware('web')
                    ->domain($domain)
                    ->group(base_path('routes/web.php'));
            }
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'module' => EnsureModuleInstalled::class,
            'role' => EnsureTenantRole::class,
            'permission' => EnsureTenantPermission::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => '/login');

        $middleware->redirectUsersTo(fn (Request $request) => tenant() ? '/dashboard' : '/tenants');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
