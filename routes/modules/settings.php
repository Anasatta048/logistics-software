<?php

use App\Http\Controllers\Settings\AppearanceController;
use App\Http\Controllers\Settings\BusinessSettingsController;
use App\Http\Controllers\Settings\NotificationPreferenceController;
use App\Http\Controllers\Settings\TimezoneController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings Module Routes
|--------------------------------------------------------------------------
|
| Appearance (nav_position/theme_mode) preference for the logged-in user.
| Rest of "Settings" (Section 6 of the UI/UX plan) is served by the existing
| profile.* routes (name/phone/password) — this module only adds the new
| Appearance slice the plan calls for.
*/
Route::middleware(['auth'])->group(function () {
    Route::patch('settings/appearance', [AppearanceController::class, 'update'])
        ->name('settings.appearance');

    Route::patch('settings/notifications', [NotificationPreferenceController::class, 'update'])
        ->name('settings.notifications');

    // The settings hub (business profile, finance, operations), drawn from the registry.
    Route::get('settings/business/{group?}', [BusinessSettingsController::class, 'edit'])
        ->name('settings.business.edit')
        ->middleware('can:manage-settings');
    Route::patch('settings/business/{group}', [BusinessSettingsController::class, 'update'])
        ->name('settings.business.update')
        ->middleware('can:manage-settings');

    // Admin-only: the team timezones shown beside every date-time.
    Route::get('settings/timezones', [TimezoneController::class, 'edit'])
        ->name('settings.timezones.edit')
        ->middleware('can:manage-settings');
    Route::patch('settings/timezones', [TimezoneController::class, 'update'])
        ->name('settings.timezones.update')
        ->middleware('can:manage-settings');
});
