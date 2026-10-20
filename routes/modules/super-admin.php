<?php

use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\TenantRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
| The platform owner's area: subscription plans and incoming access requests.
|
*/

Route::middleware(['auth', 'can:manage-saas'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('plans', PlanController::class)->except('show');

        Route::get('requests', [TenantRequestController::class, 'index'])->name('requests.index');
        Route::get('requests/{tenantRequest}', [TenantRequestController::class, 'show'])->name('requests.show');
        Route::put('requests/{tenantRequest}', [TenantRequestController::class, 'update'])->name('requests.update');
        Route::delete('requests/{tenantRequest}', [TenantRequestController::class, 'destroy'])->name('requests.destroy');
    });
