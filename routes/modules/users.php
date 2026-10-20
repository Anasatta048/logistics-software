<?php

use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Module Routes
|--------------------------------------------------------------------------
|
| Self-service profile routes plus Admin user management. Every route for this
| module lives here so routes/web.php stays a list of requires.
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')
    ->prefix('users')
    ->name('users.')
    ->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ->name('index')
            ->middleware('can:viewAny,App\Models\User');
        Route::get('/create', [UserController::class, 'create'])
            ->name('create')
            ->middleware('can:create,App\Models\User');
        Route::post('/', [UserController::class, 'store'])
            ->name('store')
            ->middleware('can:create,App\Models\User');
        Route::get('/{user}/edit', [UserController::class, 'edit'])
            ->name('edit')
            ->middleware('can:update,user');
        Route::put('/{user}', [UserController::class, 'update'])
            ->name('update')
            ->middleware('can:update,user');
    });
