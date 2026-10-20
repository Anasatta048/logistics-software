<?php

use App\Http\Controllers\Data\ExportController;
use App\Http\Controllers\Data\ImportController;
use Illuminate\Support\Facades\Route;

/*
| Bringing records in from a spreadsheet and taking a full backup out.
| Importing and exporting are separate rights (data.import / data.export).
*/
Route::middleware('auth')->prefix('data')->name('data.')->group(function () {
    Route::middleware('can:data.import')->group(function () {
        Route::get('/import/{entity}/template', [ImportController::class, 'template'])->name('import.template');
        Route::post('/import/{entity}', [ImportController::class, 'upload'])->name('import.upload');
        Route::get('/import/{entity}/{token}', [ImportController::class, 'preview'])->name('import.preview');
        Route::post('/import/{entity}/{token}', [ImportController::class, 'commit'])->name('import.commit');
        Route::get('/import/{entity}/{token}/rejected', [ImportController::class, 'errors'])->name('import.errors');
    });

    Route::get('/', [ImportController::class, 'index'])->name('index')->middleware('can:manage-data');
    Route::post('/export', [ExportController::class, 'store'])->name('export.store')->middleware('can:data.export');
    Route::get('/export/{dataExport}', [ExportController::class, 'download'])->name('export.download')->middleware('can:data.export');
});
