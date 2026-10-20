<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Timezones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin-only: the US timezones every date-time in the app is shown in. The
 * first row is the entry zone (what dispatchers type), the rest are shown
 * alongside it.
 */
final class TimezoneController extends Controller
{
    public const MAX_ZONES = 6;

    public function edit(): View
    {
        return view('profile.edit', [
            'user' => request()->user(),
            'tab' => 'timezones',
            'zones' => Timezones::zones(),
            'options' => Timezones::usOptions(),
            'max' => self::MAX_ZONES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'zones' => ['required', 'array', 'min:1', 'max:'.self::MAX_ZONES],
            'zones.*.label' => ['required', 'string', 'max:16'],
            'zones.*.timezone' => ['required', 'string', Rule::in(Timezones::usIdentifiers())],
        ], [
            'zones.*.label.required' => 'Give every timezone a short label, e.g. ET.',
            'zones.*.timezone.required' => 'Pick a timezone for every row.',
            'zones.*.timezone.in' => 'Pick a US timezone.',
        ]);

        Setting::write(Timezones::SETTING, array_map(
            fn (array $zone): array => ['label' => trim($zone['label']), 'timezone' => $zone['timezone']],
            array_values($validated['zones']),
        ));
        Timezones::flush();

        return redirect()->route('settings.timezones.edit')->with('success', 'Timezones updated.');
    }
}
