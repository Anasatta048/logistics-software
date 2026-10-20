<?php

use App\Http\Controllers\Load\LoadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Load Module Routes
|--------------------------------------------------------------------------
|
| The core dispatch module. There is no delete route: a load is cancelled, not
| removed, so its status history, attachments and financial records survive
| (SRD 6.3 / 12).
|
*/

Route::middleware('auth')->prefix('loads')->name('loads.')->group(function () {
    // Load-form utility: the map picker posts the two pin coordinates here and
    // gets driving miles back. Registered before /{load}/... so the literal
    // segment is never read as a load id.
    Route::post('/calculate-distance', [LoadController::class, 'calculateDistance'])
        ->name('calculate-distance');

    Route::get('/', [LoadController::class, 'index'])
        ->name('index')
        ->middleware('can:viewAny,App\Models\Load');
    Route::get('/create', [LoadController::class, 'create'])
        ->name('create')
        ->middleware('can:create,App\Models\Load');
    Route::post('/', [LoadController::class, 'store'])
        ->name('store')
        ->middleware('can:create,App\Models\Load');
    Route::get('/{load}', [LoadController::class, 'show'])
        ->name('show')
        ->middleware('can:view,load')
        ->whereNumber('load');
    Route::get('/{load}/attachments/{attachment}/download', [LoadController::class, 'downloadAttachment'])
        ->name('attachments.download')
        ->middleware('can:view,load')
        ->whereNumber(['load', 'attachment']);
    Route::get('/{load}/edit', [LoadController::class, 'edit'])
        ->name('edit')
        ->middleware('can:update,load');
    Route::put('/{load}', [LoadController::class, 'update'])
        ->name('update')
        ->middleware('can:update,load');
    Route::post('/{load}/status', [LoadController::class, 'changeStatus'])
        ->name('status')
        // No `can:` middleware here: the per-step gate (loads.load / loads.paid) lives in LoadPolicy::advanceStatus, which needs the target
        // status the route can't see, so the controller authorizes it.
        ->middleware('auth');
    Route::post('/{load}/cancel', [LoadController::class, 'cancel'])
        ->name('cancel')
        ->middleware('can:cancel,load');
    Route::post('/{load}/attachments', [LoadController::class, 'attachDocument'])
        ->name('attachments.store')
        ->middleware('can:update,load');
});
