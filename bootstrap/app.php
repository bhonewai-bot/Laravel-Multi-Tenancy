<?php

use App\Http\Controllers\CloudflareHostnameChallengeController;
use App\Http\Controllers\DomainCheckController;
use App\Http\Middleware\EnsureCentralAdmin;
use App\Http\Middleware\EnsureModuleInstalled;
use App\Http\Middleware\EnsureTenantPermission;
use App\Http\Middleware\EnsureTenantRole;
use App\Support\AppHome;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function () {
            $centralDomains = config('tenancy.central_domains');

            // Caddy on-demand TLS ask endpoint.
            // Must be host-agnostic because Caddy calls via docker service host (e.g. nginx).
            Route::get('/internal/domain-check', DomainCheckController::class)
                ->middleware('throttle:120,1');

            // Cloudflare validates pending custom hostnames before tenancy can trust the domain,
            // so this endpoint must stay host-agnostic instead of being limited to central domains.
            Route::middleware('web')->get(
                '/.well-known/cf-custom-hostname-challenge/{hostnameId}',
                CloudflareHostnameChallengeController::class
            )->name('cloudflare.hostname-challenge');

            foreach ($centralDomains as $domain) {
                Route::domain($domain)->group(function () {
                    Route::middleware('web')->group(base_path('routes/web.php'));
                });
            }
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'central.admin' => EnsureCentralAdmin::class,
            'module' => EnsureModuleInstalled::class,
            'role' => EnsureTenantRole::class,
            'permission' => EnsureTenantPermission::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => '/login');
        $middleware->redirectUsersTo(fn (Request $request) => AppHome::path());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TenantCouldNotBeIdentifiedOnDomainException $exception, Request $request) {
            return response('Not Found', 404);
        });
    })->create();
