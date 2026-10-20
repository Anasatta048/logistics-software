<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * UI_UX_DESIGN_PLAN.md Section 2.2 — persists the logged-in user's
 * Sidebar/Topbar and light/dark preference. A plain form submit + redirect
 * back is used rather than a hand-rolled fetch() call: there is no SPA/JS
 * framework in this stack, the toggle is used rarely (a Settings-page
 * action, not a per-request control), and a full reload of the shell is
 * cheap and unsurprising for a server-rendered Blade app. Documented here
 * per the plan's request to note the AJAX-vs-reload choice.
 */
final class AppearanceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nav_position' => ['sometimes', 'in:sidebar,topbar'],
            'theme_mode' => ['required', 'in:light,dark,system'],
        ]);

        $request->user()->update($validated);

        return back()->with('success', 'Appearance updated.');
    }
}
