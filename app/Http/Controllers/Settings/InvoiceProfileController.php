<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\InvoiceProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Admin-only: the business details printed on every invoice. */
final class InvoiceProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'website' => ['nullable', 'string', 'max:120'],
        ]);

        Setting::write(InvoiceProfile::SETTING, $validated);

        return redirect()->to(route('profile.edit').'#invoice')->with('success', 'Invoice details saved.');
    }
}
