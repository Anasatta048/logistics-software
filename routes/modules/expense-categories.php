<?php

use App\Http\Controllers\Expense\ExpenseCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('expense-categories')->name('expense-categories.')->group(function () {
    Route::get('/', [ExpenseCategoryController::class, 'index'])->name('index')->middleware('can:expense-categories.view');
    Route::post('/', [ExpenseCategoryController::class, 'store'])->name('store')->middleware('can:expense-categories.create');
    Route::put('/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('update')->middleware('can:expense-categories.edit');
    Route::delete('/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('destroy')->middleware('can:expense-categories.delete');
});
