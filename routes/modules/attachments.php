<?php

use App\Http\Controllers\Support\AttachmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attachment downloads
|--------------------------------------------------------------------------
| Files are on the private disk; each route authorizes against the record's own
| policy, so only someone who may view the payment / expense / voucher gets the file.
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/company-payments/{companyPayment}/attachment', [AttachmentController::class, 'companyPayment'])
        ->name('company-payments.attachment')->whereNumber('companyPayment');
    Route::get('/expenses/{expense}/attachment', [AttachmentController::class, 'expense'])
        ->name('expenses.attachment')->whereNumber('expense');
    Route::get('/journal-vouchers/{journalVoucher}/attachment', [AttachmentController::class, 'journalVoucher'])
        ->name('journal-vouchers.attachment')->whereNumber('journalVoucher');
});
