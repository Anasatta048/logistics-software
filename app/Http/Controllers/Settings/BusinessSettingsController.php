<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use App\Support\InvoiceProfile;
use App\Support\Settings\TenantSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The settings hub: one screen, drawn from config/tenant-settings.php. */
final class BusinessSettingsController extends Controller
{
    public function edit(?string $group = null): View
    {
        $groups = TenantSettings::groups();
        $group ??= array_key_first($groups);

        abort_unless(isset($groups[$group]), 404);

        return view('settings.business', [
            'groups' => $groups,
            'group' => $group,
            'schema' => $groups[$group],
            'values' => TenantSettings::all($group),
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        abort_unless(isset(TenantSettings::groups()[$group]), 404);

        $request->validate(TenantSettings::rules($group));

        TenantSettings::save($group, $request->all());
        InvoiceProfile::apply();

        return redirect()
            ->route('settings.business.edit', $group)
            ->with('success', TenantSettings::groups()[$group]['label'].' saved.');
    }
}
