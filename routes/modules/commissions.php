<?php

use App\Http\Controllers\Commission\CommissionController;
use App\Http\Controllers\Commission\CommissionPayoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Commission Module Routes
|--------------------------------------------------------------------------
|
| The amount is accrued by the `LoadPaid` listener and reversed by the
| `LoadCancelled` listener, so nothing hand-edits it. The one write is paying a
| dispatcher: any USD amount (part payment, full balance or advance), converted
| to PKR at a typed rate and taken out of an account. A dispatcher is scoped to
| their own rows inside CommissionService, not just the policy.
|
*/

Route::middleware('auth')->prefix('commissions')->name('commissions.')->group(function () {
    Route::get('/', [CommissionController::class, 'index'])
        ->name('index')
        ->middleware('can:viewAny,App\Models\Commission');

    // Registered before the parameterised ledger route.
    Route::get('/payouts/create', [CommissionPayoutController::class, 'create'])
        ->name('payouts.create')
        ->middleware('can:pay,App\Models\Commission');
    Route::post('/payouts', [CommissionPayoutController::class, 'store'])
        ->name('payouts.store')
        ->middleware('can:pay,App\Models\Commission');

    Route::get('/ledger/{user}', [CommissionController::class, 'ledger'])
        ->name('ledger')
        ->middleware('can:viewCommissionLedger,user');
});
