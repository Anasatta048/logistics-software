<?php

use App\Http\Controllers\Report\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Report Module Routes
|--------------------------------------------------------------------------
|
| Read-only reports, one per stage of the business flow:
|   Loads (register, drivers, brokers) → Invoices & payments (invoice register,
|   receivables, receipts, PKR conversions) → Accounts (expenses, statement)
|   → Commissions → Profit & loss.
|
| Each route is gated by its own named `reports.{report}` Gate (registered in
| AppServiceProvider, since Report has no Eloquent model to hang a discoverable
| Policy off of); dispatcher scoping down to their own records happens in
| ReportService, not here.
*/
Route::middleware('auth')->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');

    // Loads
    Route::get('/loads', [ReportController::class, 'loads'])->name('loads')->middleware('can:reports.loads');
    Route::get('/drivers', [ReportController::class, 'drivers'])->name('drivers')->middleware('can:reports.drivers');
    Route::get('/brokers', [ReportController::class, 'brokers'])->name('brokers')->middleware('can:reports.brokers');

    // Invoices & payments
    Route::get('/invoices', [ReportController::class, 'invoices'])->name('invoices')->middleware('can:reports.invoices');
    Route::get('/receivables', [ReportController::class, 'receivables'])->name('receivables')->middleware('can:reports.receivables');
    Route::get('/receipts', [ReportController::class, 'receipts'])->name('receipts')->middleware('can:reports.receipts');
    Route::get('/conversions', [ReportController::class, 'conversions'])->name('conversions')->middleware('can:reports.conversions');

    // Accounts
    Route::get('/expenses', [ReportController::class, 'expenses'])->name('expenses')->middleware('can:reports.expenses');
    Route::get('/account-statement', [ReportController::class, 'accountStatement'])->name('account-statement')->middleware('can:reports.accountStatement');

    // Commissions & summary
    Route::get('/dispatcher-commissions', [ReportController::class, 'dispatcherCommissions'])->name('dispatcher-commissions')->middleware('can:reports.dispatcherCommissions');
    Route::get('/profit-and-loss', [ReportController::class, 'profitAndLoss'])->name('profit-and-loss')->middleware('can:reports.profitAndLoss');
});
