<?php

use App\Http\Controllers\Billing\BillingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Billing Routes (a workspace's own subscription)
|--------------------------------------------------------------------------
| Also open while the workspace is locked, so an expired customer can pay.
|
*/

Route::middleware(['auth', 'can:manage-billing'])->prefix('billing')->name('billing.')->group(function () {
    Route::get('/', [BillingController::class, 'index'])->name('index');
    Route::post('/payments', [BillingController::class, 'store'])->middleware('throttle:10,60')->name('payments.store');
    Route::get('/payments/{payment}/proof', [BillingController::class, 'proof'])->name('payments.proof')->whereNumber('payment');
    Route::get('/payments/{payment}/receipt', [BillingController::class, 'receipt'])->name('payments.receipt')->whereNumber('payment');
});
