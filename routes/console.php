<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reports:send-scheduled')->hourly()->onOneServer();
Schedule::command('tenants:sweep')->dailyAt('02:00')->onOneServer();
Schedule::command('tenants:remind')->dailyAt('09:00')->onOneServer();

// Backups: nightly dump; Sundays also prove the dump restores. A failure emails the support address.
Schedule::command('backup:run')->dailyAt('03:00')->onOneServer()
    ->when(fn () => (bool) config('saas.support_email'))
    ->emailOutputOnFailure((string) config('saas.support_email'));
Schedule::command('backup:run --verify')->weeklyOn(0, '03:30')->onOneServer()
    ->when(fn () => (bool) config('saas.support_email'))
    ->emailOutputOnFailure((string) config('saas.support_email'));
