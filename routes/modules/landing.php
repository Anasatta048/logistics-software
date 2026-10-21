<?php

use App\Http\Controllers\Demo\DemoController;
use App\Http\Controllers\Landing\LandingController;
use App\Http\Controllers\Landing\LegalController;
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
    ->middleware(['throttle:6,1', 'throttle:20,60'])
    ->name('request-access.store');

Route::get('/legal/{page}', LegalController::class)
    ->whereIn('page', array_keys(LegalController::PAGES))
    ->name('legal.show');

// The live demo: a private one-hour sandbox. Guests only; throttled per IP on top of the daily allowance in DemoService.
Route::post('/demo/start', [DemoController::class, 'start'])
    ->middleware(['guest', 'throttle:5,10'])
    ->name('demo.start');
