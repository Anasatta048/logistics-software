<?php

use App\Http\Controllers\Broker\BrokerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Broker Module Routes
|--------------------------------------------------------------------------
|
| Master data for brokers / customers. `search` is the lookup endpoint the Load
| form (Phase 3) calls for its broker picker, so it is registered before the
| parameterised routes.
|
*/

Route::middleware('auth')->prefix('brokers')->name('brokers.')->group(function () {
    Route::get('/search', [BrokerController::class, 'search'])
        ->name('search')
        ->middleware('can:viewAny,App\Models\Broker');

    Route::get('/', [BrokerController::class, 'index'])
        ->name('index')
        ->middleware('can:viewAny,App\Models\Broker');
    Route::get('/create', [BrokerController::class, 'create'])
        ->name('create')
        ->middleware('can:create,App\Models\Broker');
    Route::post('/', [BrokerController::class, 'store'])
        ->name('store')
        ->middleware('can:create,App\Models\Broker');
    Route::get('/{broker}/edit', [BrokerController::class, 'edit'])
        ->name('edit')
        ->middleware('can:update,broker');
    Route::put('/{broker}', [BrokerController::class, 'update'])
        ->name('update')
        ->middleware('can:update,broker');
    Route::delete('/{broker}', [BrokerController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:delete,broker');
});
