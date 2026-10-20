<?php

use App\Support\Settings\TenantSettings;
use Illuminate\Support\Carbon;

/*
| Tiny view helpers for the company-level "Regional" settings, so a Blade
| file can say df() or cur_local() instead of hard-coding "M j, Y" or "Rs".
*/

if (! function_exists('df')) {
    /** The company's date format, e.g. "M j, Y" or "d/m/Y". */
    function df(): string
    {
        return (string) TenantSettings::get('regional.date_format', 'M j, Y');
    }
}

if (! function_exists('cur_local')) {
    /** Symbol for the money the company keeps (default "Rs"). */
    function cur_local(): string
    {
        return (string) TenantSettings::get('regional.local_currency', 'Rs');
    }
}

if (! function_exists('cur_foreign')) {
    /** Symbol for the money clients pay in (default "$"). */
    function cur_foreign(): string
    {
        return (string) TenantSettings::get('regional.foreign_currency', '$');
    }
}

if (! function_exists('week_start_day')) {
    /** Carbon weekday constant for the company's first day of the week. */
    function week_start_day(): int
    {
        return match (TenantSettings::get('regional.week_start', 'monday')) {
            'sunday' => Carbon::SUNDAY,
            'saturday' => Carbon::SATURDAY,
            default => Carbon::MONDAY,
        };
    }
}

if (! function_exists('fiscal_year_start')) {
    /** First day of the financial year that contains $on (default: today). */
    function fiscal_year_start(?Carbon $on = null): Carbon
    {
        $on ??= Carbon::today();
        $month = max(1, min(12, (int) TenantSettings::get('regional.fiscal_year_start', 7)));
        $start = Carbon::create($on->year, $month, 1)->startOfDay();

        return $start->gt($on) ? $start->subYear() : $start;
    }
}
