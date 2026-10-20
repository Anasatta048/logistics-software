<?php

use App\Http\Controllers\Landing\LandingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Landing Routes
|--------------------------------------------------------------------------
| The marketing page and its "request access" form. Open to everyone.
|
*/

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::post('/request-access', [LandingController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('request-access.store');
