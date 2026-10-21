<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\PlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PlanController extends Controller
{
    public function index(): View
    {
        return view('super-admin.plans.index', [
            'plans' => Plan::withCount(['requests', 'tenants'])->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('super-admin.plans.create', ['plan' => null]);
    }

    public function store(PlanRequest $request): RedirectResponse
    {
        $plan = Plan::create($request->planData());

        return redirect()->route('super-admin.plans.index')->with('success', "Plan {$plan->name} created.");
    }

    public function edit(Plan $plan): View
    {
        return view('super-admin.plans.edit', ['plan' => $plan]);
    }

    public function update(PlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->planData());

        return redirect()->route('super-admin.plans.index')->with('success', "Plan {$plan->name} updated.");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        // A tenant whose plan vanishes would silently lose every cap and module limit.
        if ($plan->tenants()->exists()) {
            return back()->with('error', "Plan {$plan->name} is used by {$plan->tenants()->count()} workspace(s). Move them to another plan first, or hide it from the landing page instead.");
        }

        // Requests keep their history (plan_id is nulled).
        $plan->delete();

        return redirect()->route('super-admin.plans.index')->with('success', "Plan {$plan->name} removed.");
    }
}
