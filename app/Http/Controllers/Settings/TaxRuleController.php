<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TaxRule;
use App\Support\Tenancy\TenantRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Settings -> Tax rules: the named taxes picked when USD is converted to PKR. */
final class TaxRuleController extends Controller
{
    public function index(): View
    {
        return view('settings.tax-rules', ['rules' => TaxRule::query()->orderByDesc('is_default')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', TenantRule::unique('tax_rules', 'name')],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $rule = TaxRule::create($data + ['is_default' => TaxRule::query()->doesntExist()]);

        return back()->with('success', "Tax rule {$rule->name} added.");
    }

    public function update(Request $request, TaxRule $taxRule): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', TenantRule::unique('tax_rules', 'name')->ignore($taxRule)],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        // Only one default: it is the one pre-selected on the conversion form.
        if ($request->boolean('is_default')) {
            TaxRule::query()->whereKeyNot($taxRule->id)->update(['is_default' => false]);
        }

        $taxRule->update($data + ['is_default' => $request->boolean('is_default'), 'is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Tax rule saved.');
    }

    public function destroy(TaxRule $taxRule): RedirectResponse
    {
        // Past conversions keep their own type and value, so a rule is always safe to remove.
        $taxRule->delete();

        return back()->with('success', 'Tax rule removed.');
    }
}
