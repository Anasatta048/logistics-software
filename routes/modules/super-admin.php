<?php

use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\PaymentController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\TenantController;
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

        Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
        Route::put('tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
        Route::post('tenants/{tenant}/extend-trial', [TenantController::class, 'extendTrial'])->name('tenants.extend-trial');
        Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{tenant}/cancel', [TenantController::class, 'cancel'])->name('tenants.cancel');
        Route::post('tenants/{tenant}/enter', [TenantController::class, 'enter'])->name('tenants.enter');
        Route::post('leave-workspace', [TenantController::class, 'leave'])->name('tenants.leave');

        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}/proof', [PaymentController::class, 'proof'])->name('payments.proof')->whereNumber('payment');
        Route::post('payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve')->whereNumber('payment');
        Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject')->whereNumber('payment');

        Route::get('requests', [TenantRequestController::class, 'index'])->name('requests.index');
        Route::post('requests/{tenantRequest}/approve', [TenantRequestController::class, 'approve'])->name('requests.approve');
        Route::get('requests/{tenantRequest}', [TenantRequestController::class, 'show'])->name('requests.show');
        Route::put('requests/{tenantRequest}', [TenantRequestController::class, 'update'])->name('requests.update');
        Route::delete('requests/{tenantRequest}', [TenantRequestController::class, 'destroy'])->name('requests.destroy');
    });
