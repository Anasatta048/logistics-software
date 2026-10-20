<?php

use App\Http\Controllers\Driver\DriverController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Driver Module Routes
|--------------------------------------------------------------------------
|
| Master data for drivers / owner-operators. `search` is the lookup endpoint the
| Load form (Phase 3) calls for its driver picker, so it is registered before
| the parameterised routes.
|
*/

Route::middleware('auth')->prefix('drivers')->name('drivers.')->group(function () {
    Route::get('/search', [DriverController::class, 'search'])
        ->name('search')
        ->middleware('can:viewAny,App\Models\Driver');

    Route::get('/', [DriverController::class, 'index'])
        ->name('index')
        ->middleware('can:viewAny,App\Models\Driver');
    Route::get('/create', [DriverController::class, 'create'])
        ->name('create')
        ->middleware('can:create,App\Models\Driver');
    Route::post('/', [DriverController::class, 'store'])
        ->name('store')
        ->middleware('can:create,App\Models\Driver');
    Route::get('/{driver}/edit', [DriverController::class, 'edit'])
        ->name('edit')
        ->middleware('can:update,driver');
    Route::put('/{driver}', [DriverController::class, 'update'])
        ->name('update')
        ->middleware('can:update,driver');
    Route::delete('/{driver}', [DriverController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:delete,driver');
});
