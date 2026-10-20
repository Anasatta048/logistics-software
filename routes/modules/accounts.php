<?php

use App\Http\Controllers\Account\AccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Account Module Routes
|--------------------------------------------------------------------------
|
| Cash/Bank accounts. `AccountService::recordTransaction()` is the only code
| path allowed to change a balance (Phase 7); this controller just surfaces
| CRUD and the ledger view.
|
*/

Route::middleware('auth')->prefix('accounts')->name('accounts.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])
        ->name('index')
        ->middleware('can:viewAny,App\Models\Account');
    Route::get('/create', [AccountController::class, 'create'])
        ->name('create')
        ->middleware('can:create,App\Models\Account');
    Route::post('/', [AccountController::class, 'store'])
        ->name('store')
        ->middleware('can:create,App\Models\Account');
    Route::get('/{account}/edit', [AccountController::class, 'edit'])
        ->name('edit')
        ->middleware('can:update,account');
    Route::put('/{account}', [AccountController::class, 'update'])
        ->name('update')
        ->middleware('can:update,account');
    Route::delete('/{account}', [AccountController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:delete,account');
    Route::get('/{account}/ledger', [AccountController::class, 'ledger'])
        ->name('ledger')
        ->middleware('can:view,account');
});
