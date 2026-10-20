<?php

use App\Http\Controllers\Expense\ExpenseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Expense Module Routes
|--------------------------------------------------------------------------
|
| Company costs, optionally booked against a load so they net out of that
| load's commission margin (SRD 6.3). Read/write is accountant territory; a
| dispatcher may only book costs against their own loads, which the service
| query and StoreExpenseRequest enforce.
|
*/

Route::middleware('auth')->prefix('expenses')->name('expenses.')->group(function () {
    Route::get('/', [ExpenseController::class, 'index'])
        ->name('index')
        ->middleware('can:viewAny,App\Models\Expense');
    Route::get('/create', [ExpenseController::class, 'create'])
        ->name('create')
        ->middleware('can:create,App\Models\Expense');
    Route::post('/', [ExpenseController::class, 'store'])
        ->name('store')
        ->middleware('can:create,App\Models\Expense');
    Route::get('/{expense}/edit', [ExpenseController::class, 'edit'])
        ->name('edit')
        ->middleware('can:update,expense');
    Route::put('/{expense}', [ExpenseController::class, 'update'])
        ->name('update')
        ->middleware('can:update,expense');
    Route::delete('/{expense}', [ExpenseController::class, 'destroy'])
        ->name('destroy')
        ->middleware('can:delete,expense');
});
