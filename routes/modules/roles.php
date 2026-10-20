<?php

use App\Http\Controllers\Role\RoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Role Module Routes
|--------------------------------------------------------------------------
| Creating a role and ticking its rights. Users get their access by picking a
| role on the user form, so there is no per-user permission editor here.
|
*/

Route::middleware('auth')
    ->prefix('roles')
    ->name('roles.')
    ->group(function () {
        Route::get('/', [RoleController::class, 'index'])
            ->name('index')
            ->middleware('can:viewAny,App\Models\User');
        Route::get('/create', [RoleController::class, 'create'])
            ->name('create')
            ->middleware('can:create,App\Models\Role');
        Route::post('/', [RoleController::class, 'store'])
            ->name('store')
            ->middleware('can:create,App\Models\Role');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])
            ->name('edit')
            ->middleware('can:update,role');
        Route::put('/{role}', [RoleController::class, 'update'])
            ->name('update')
            ->middleware('can:update,role');
        Route::delete('/{role}', [RoleController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:delete,role');
    });
