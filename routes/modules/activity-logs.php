<?php

use App\Http\Controllers\Activity\ActivityLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Activity Log Module Routes
|--------------------------------------------------------------------------
|
| Admin-only, read-only. Rows are written by ActivityObserver (module edits)
| and LogAuthActivity (sign-in/out), never through a form.
*/
Route::middleware(['auth', 'can:view-activity-logs'])->group(function () {
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
});
