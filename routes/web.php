<?php

use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ModuleRequestController;
use Illuminate\Support\Facades\Route;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', function () {
            return view('welcome');
        });

        Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::get('/modules/create', [ModuleController::class, 'create'])->name('modules.create');
        Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
        Route::post('/modules/{module}/toggle', [ModuleController::class, 'toggleStatus'])->name('modules.toggle');

        Route::get('/module-requests', [ModuleRequestController::class, 'index'])->name('module-requests.index');
        Route::post('/module-requests/{moduleRequest}/approve', [ModuleRequestController::class, 'approve'])->name('module-requests.approve');
        Route::post('/module-requests/{moduleRequest}/reject', [ModuleRequestController::class, 'reject'])->name('module-requests.reject');
    });
}
