<?php

use App\Http\Controllers\Report\SavedReportController;
use Illuminate\Support\Facades\Route;

// A person's own saved report views; each one checks the report's own right when saved.
Route::middleware(['auth', 'can:reports.viewAny'])->prefix('saved-reports')->name('saved-reports.')->group(function () {
    Route::get('/', [SavedReportController::class, 'index'])->name('index');
    Route::post('/', [SavedReportController::class, 'store'])->name('store');
    Route::put('/{savedReport}', [SavedReportController::class, 'update'])->name('update');
    Route::delete('/{savedReport}', [SavedReportController::class, 'destroy'])->name('destroy');
});
