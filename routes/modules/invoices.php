<?php

use App\Http\Controllers\Invoice\InvoiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Invoice Module Routes
|--------------------------------------------------------------------------
|
| Driver invoices: the commission a company owes on one driver's loads over a
| date range, as a screen and a PDF.
|
*/

Route::middleware('auth')->prefix('invoices')->name('invoices.')->group(function () {
    Route::get('/', [InvoiceController::class, 'index'])
        ->name('index')
        ->middleware('can:viewAny,App\Models\Invoice');
    Route::get('/create', [InvoiceController::class, 'create'])
        ->name('create')
        ->middleware('can:create,App\Models\Invoice');
    Route::get('/loads', [InvoiceController::class, 'loads'])
        ->name('loads')
        ->middleware('can:create,App\Models\Invoice');
    Route::post('/', [InvoiceController::class, 'store'])
        ->name('store')
        ->middleware('can:create,App\Models\Invoice');
    Route::get('/{invoice}', [InvoiceController::class, 'show'])
        ->name('show')
        ->middleware('can:view,invoice');
    Route::get('/{invoice}/pdf', [InvoiceController::class, 'pdf'])
        ->name('pdf')
        ->middleware('can:view,invoice');
    Route::post('/{invoice}/payments', [InvoiceController::class, 'pay'])
        ->name('pay')
        ->middleware('can:create,App\Models\CompanyPayment');
    Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:delete,invoice');
});
