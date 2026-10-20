<?php

use App\Http\Controllers\Account\JournalVoucherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| JournalVoucher Module Routes
|--------------------------------------------------------------------------
|
| A JV posts to the ledger only once approved (Phase 7 DoD). Approve and void
| are separate permissions from create/edit so a bookkeeper can raise a
| voucher without being able to post it themselves.
|
*/

Route::middleware('auth')->prefix('journal-vouchers')->name('journal-vouchers.')->group(function () {
    Route::get('/', [JournalVoucherController::class, 'index'])
        ->name('index')
        ->middleware('can:viewAny,App\Models\JournalVoucher');
    Route::get('/create', [JournalVoucherController::class, 'create'])
        ->name('create')
        ->middleware('can:create,App\Models\JournalVoucher');
    Route::post('/', [JournalVoucherController::class, 'store'])
        ->name('store')
        ->middleware('can:create,App\Models\JournalVoucher');
    Route::post('/{journal_voucher}/approve', [JournalVoucherController::class, 'approve'])
        ->name('approve')
        ->middleware('can:approve,journal_voucher');
    Route::post('/{journal_voucher}/void', [JournalVoucherController::class, 'void'])
        ->name('void')
        ->middleware('can:void,journal_voucher');
});
