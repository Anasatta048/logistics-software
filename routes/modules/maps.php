<?php

use App\Http\Controllers\Map\MapLookupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Map Lookup Routes
|--------------------------------------------------------------------------
|
| Address autocomplete and reverse geocoding for the Load form's pickers.
| Server-side so the Geoapify key stays out of the browser.
*/
Route::middleware(['auth', 'throttle:90,1'])->prefix('maps')->name('maps.')->group(function () {
    Route::get('/autocomplete', [MapLookupController::class, 'autocomplete'])->name('autocomplete');
    Route::get('/place', [MapLookupController::class, 'place'])->name('place');
    Route::get('/reverse', [MapLookupController::class, 'reverse'])->name('reverse');
});
