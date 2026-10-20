<?php

use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Company\CompanyLedgerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Company Module Routes
|--------------------------------------------------------------------------
|
| Master data for the trucking companies that own the trucks and the MC
| numbers (FLOW_UPDATE_PLAN Section 3.1). A company is picked indirectly on the
| Load form — it follows the driver — so it needs no lookup endpoint of its own.
|
*/

Route::middleware('auth')->prefix('companies')->name('companies.')->group(function () {
    // Lookup for the Load form's company picker (name or MC number).
    Route::get('/search', [CompanyController::class, 'search'])
        ->name('search')
        ->middleware('can:viewAny,App\Models\Company');

    // Money position of one company: only those who may see payments.
    Route::get('/{company}/ledger', [CompanyLedgerController::class, 'show'])
        ->name('ledger')
        ->middleware('can:viewAny,App\Models\CompanyPayment');

    Route::get('/', [CompanyController::class, 'index'])
        ->name('index')
        ->middleware('can:viewAny,App\Models\Company');
    Route::get('/create', [CompanyController::class, 'create'])
        ->name('create')
        ->middleware('can:create,App\Models\Company');
    Route::post('/', [CompanyController::class, 'store'])
        ->name('store')
        ->middleware('can:create,App\Models\Company');
    Route::get('/{company}/edit', [CompanyController::class, 'edit'])
        ->name('edit')
        ->middleware('can:update,company');
    Route::put('/{company}', [CompanyController::class, 'update'])
        ->name('update')
        ->middleware('can:update,company');
    Route::delete('/{company}', [CompanyController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:delete,company');
});
