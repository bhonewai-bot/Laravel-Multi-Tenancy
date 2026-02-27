<?php

use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ModuleRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('tenants.index');
});

/* Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard'); */

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('tenants', TenantController::class);

    Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
    Route::get('/modules/create', [ModuleController::class, 'create'])->name('modules.create');
    Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
    Route::post('/modules/{module}/toggle', [ModuleController::class, 'toggleStatus'])->name('modules.toggle');

    Route::get('/module-requests', [ModuleRequestController::class, 'index'])->name('module-requests.index');
    Route::post('/module-requests/{moduleRequest}/approve', [ModuleRequestController::class, 'approve'])->name('module-requests.approve');
    Route::post('/module-requests/{moduleRequest}/reject', [ModuleRequestController::class, 'reject'])->name('module-requests.reject');
});

require __DIR__.'/auth.php';
