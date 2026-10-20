<?php

use App\Http\Controllers\Company\CompanyPaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Company Payment Module Routes
|--------------------------------------------------------------------------
|
| The weekly USD receipt from a company (FLOW_UPDATE_PLAN Section 5). No delete
| route on purpose — see CompanyPaymentPolicy: corrections are edits until the
| receipt has been converted, and an adjustment line after that.
|
*/

Route::middleware('auth')->prefix('company-payments')->name('company-payments.')->group(function () {
    Route::get('/suggestions', [CompanyPaymentController::class, 'suggestions'])
        ->name('suggestions')
        ->middleware('can:viewAny,App\Models\CompanyPayment');

    // Outstanding + advance for the chosen company, so the form can show what
    // the money is for before an amount is typed.
    Route::get('/balance', [CompanyPaymentController::class, 'balance'])
        ->name('balance')
        ->middleware('can:viewAny,App\Models\CompanyPayment');

    Route::get('/', [CompanyPaymentController::class, 'index'])
        ->name('index')
        ->middleware('can:viewAny,App\Models\CompanyPayment');
    Route::get('/create', [CompanyPaymentController::class, 'create'])
        ->name('create')
        ->middleware('can:create,App\Models\CompanyPayment');
    Route::post('/', [CompanyPaymentController::class, 'store'])
        ->name('store')
        ->middleware('can:create,App\Models\CompanyPayment');
    Route::get('/{companyPayment}', [CompanyPaymentController::class, 'show'])
        ->name('show')
        ->middleware('can:view,companyPayment');
    Route::get('/{companyPayment}/edit', [CompanyPaymentController::class, 'edit'])
        ->name('edit')
        ->middleware('can:update,companyPayment');
    Route::put('/{companyPayment}', [CompanyPaymentController::class, 'update'])
        ->name('update')
        ->middleware('can:update,companyPayment');
    // Convert an already-recorded payment to PKR (the same fields as the form's
    // optional "Convert to PKR" section).
    Route::post('/{companyPayment}/convert', [CompanyPaymentController::class, 'convert'])
        ->name('convert')
        ->middleware('can:convert,companyPayment');
});
