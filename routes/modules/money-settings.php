<?php

use App\Http\Controllers\Settings\CommissionTierController;
use App\Http\Controllers\Settings\TaxRuleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:manage-settings'])->prefix('settings')->group(function () {
    Route::get('tax-rules', [TaxRuleController::class, 'index'])->name('tax-rules.index');
    Route::post('tax-rules', [TaxRuleController::class, 'store'])->name('tax-rules.store');
    Route::put('tax-rules/{taxRule}', [TaxRuleController::class, 'update'])->name('tax-rules.update');
    Route::delete('tax-rules/{taxRule}', [TaxRuleController::class, 'destroy'])->name('tax-rules.destroy');

    Route::get('commission-tiers', [CommissionTierController::class, 'edit'])->name('commission-tiers.edit');
    Route::put('commission-tiers', [CommissionTierController::class, 'update'])->name('commission-tiers.update');
});
