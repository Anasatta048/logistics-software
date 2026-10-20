<?php

use App\Http\Controllers\Settings\CustomFieldController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:manage-settings'])->prefix('settings/custom-fields')->name('custom-fields.')->group(function () {
    Route::get('/', [CustomFieldController::class, 'index'])->name('index');
    Route::post('/', [CustomFieldController::class, 'store'])->name('store');
    Route::put('/{customField}', [CustomFieldController::class, 'update'])->name('update');
    Route::delete('/{customField}', [CustomFieldController::class, 'destroy'])->name('destroy');
});
