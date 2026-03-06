<?php

declare(strict_types=1);

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tenant\DomainController;
use App\Http\Controllers\Tenant\ModuleRequestController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Middleware\EnsureVerifiedTenantDomain;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    EnsureVerifiedTenantDomain::class
])->group(function () {
    Route::get('/', function () {
        return redirect('dashboard');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('tenant.profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('tenant.profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('tenant.profile.destroy');

        Route::get('/modules', [ModuleRequestController::class, 'index'])->name('tenant.modules.index');
        Route::post('/modules/request', [ModuleRequestController::class, 'request'])->name('tenant.modules.request');

        Route::post('/modules/install', [ModuleRequestController::class, 'install'])->name('tenant.modules.install');
        Route::post('/modules/uninstall', [ModuleRequestController::class, 'uninstall'])->name('tenant.modules.uninstall');
        Route::get('/me/permissions', fn () => 'Ok')
            ->middleware('permission:user.read')
            ->name('tenant.permissions');

        Route::resource('users', UserController::class)->names('tenant.users');
        Route::resource('roles', RoleController::class)->names('tenant.roles');

        // Custom Domain
        Route::get('/domains', [DomainController::class, 'index'])
            ->middleware('permission:domain.read')
            ->name('tenant.domains.index');

        Route::get('/domains/create', [DomainController::class, 'create'])
            ->middleware('permission:domain.create')
            ->name('tenant.domains.create');

        Route::post('/domains', [DomainController::class, 'store'])
            ->middleware(['permission:domain.create', 'throttle:20,1'])
            ->name('tenant.domains.store');

        Route::post('/domains/{domain}/verify', [DomainController::class, 'verify'])
            ->middleware(['permission:domain.verify', 'throttle:30,1'])
            ->name('tenant.domains.verify');

        Route::delete('/domains/{domain}', [DomainController::class, 'destroy'])
            ->middleware('permission:domain.delete')
            ->name('tenant.domains.destroy');
    });

    require __DIR__.'/auth.php';
});
