<?php

use App\Http\Controllers\Account\AccountTransferController;
use Illuminate\Support\Facades\Route;

/*
| Money moved between the company's own accounts. Viewing needs accounts.view;
| making or reversing a transfer needs accounts.transfer.
*/
Route::middleware('auth')->prefix('account-transfers')->name('account-transfers.')->group(function () {
    Route::get('/', [AccountTransferController::class, 'index'])->name('index')->middleware('can:accounts.view');
    Route::get('/create', [AccountTransferController::class, 'create'])->name('create')->middleware('can:accounts.transfer');
    Route::post('/', [AccountTransferController::class, 'store'])->name('store')->middleware('can:accounts.transfer');
    Route::post('/{accountTransfer}/reverse', [AccountTransferController::class, 'reverse'])->name('reverse')->middleware('can:accounts.transfer');
});
