<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Settings → Notifications (UI_UX_DESIGN_PLAN.md Section 6). Persists which
 * load events notify this user; the 4 SendLoad*Notification listeners read
 * this same column before dispatching (see App\Listeners).
 */
final class NotificationPreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $keys = array_keys(User::defaultNotificationPreferences());

        $validated = $request->validate([
            'preferences' => ['array'],
            'preferences.*' => ['in:'.implode(',', $keys)],
        ]);

        $enabled = $validated['preferences'] ?? [];

        $preferences = collect($keys)
            ->mapWithKeys(fn (string $key) => [$key => in_array($key, $enabled, true)])
            ->all();

        $request->user()->update(['notification_preferences' => $preferences]);

        return back()->with('success', 'Notification preferences updated.');
    }
}
