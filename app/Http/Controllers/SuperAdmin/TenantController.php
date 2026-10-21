<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Services\Tenancy\TenantLimits;
use App\Services\Tenancy\SubscriptionService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $status = TenantStatus::tryFrom((string) $request->query('status'));

        return view('super-admin.tenants.index', [
            'tenants' => Tenant::query()
                ->with('plan:id,name')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'counts' => Tenant::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Tenant $tenant, TenantContext $context, TenantLimits $limits): View
    {
        $tenant->load('plan');

        // Usage is read inside the tenant's context so the counts are its own.
        $usage = $context->run($tenant, fn (): array => collect(array_keys(TenantLimits::RESOURCES))
            ->mapWithKeys(fn (string $resource): array => [$resource => [
                'used' => $limits->used($resource),
                'limit' => $limits->limit($resource),
            ]])->all());

        return view('super-admin.tenants.show', [
            'tenant' => $tenant,
            'usage' => $usage,
            'plans' => Plan::query()->orderBy('sort_order')->get(['id', 'name']),
            'payments' => $tenant->payments()->latest()->limit(10)->get(),
            'logs' => PlatformAuditLog::query()->with('user:id,name')->where('tenant_id', $tenant->id)->latest('created_at')->limit(15)->get(),
        ]);
    }

    public function update(Request $request, Tenant $tenant, TenantContext $context, TenantLimits $limits): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            // A hidden plan can stay on the tenant that already has it, but can't be handed to others.
            'plan_id' => ['required', Rule::exists('plans', 'id')->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $tenant->plan_id))],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ((int) $data['plan_id'] !== (int) $tenant->plan_id) {
            // Downgrades must fit: read the tenant's own usage against the new plan's caps.
            $over = $context->run($tenant, fn (): array => $limits->exceededBy(Plan::findOrFail($data['plan_id'])));

            if ($over !== []) {
                throw ValidationException::withMessages([
                    'plan_id' => 'This workspace already uses '.implode(', ', $over).'. Reduce its usage or pick a bigger plan.',
                ]);
            }
        }

        $before = $tenant->only(['plan_id', 'billing_cycle']);
        $tenant->update($data);

        if ($before['plan_id'] !== $tenant->plan_id || $before['billing_cycle'] !== $tenant->billing_cycle) {
            PlatformAuditLog::record('tenant.plan_changed', $tenant->id, 'Plan or billing cycle changed', ['from' => $before, 'to' => $tenant->only(['plan_id', 'billing_cycle'])]);
        }

        return back()->with('success', 'Workspace saved.');
    }

    /** A payment arrived: extend the paid period by one billing cycle and (re)activate. */
    public function activate(Request $request, Tenant $tenant, SubscriptionService $subscriptions): RedirectResponse
    {
        $payment = $subscriptions->recordManual($tenant, $request->user());

        return back()->with('success', 'Active until '.$payment->period_end->format('d M Y').". Receipt {$payment->receipt_number} issued.");
    }

    public function extendTrial(Request $request, Tenant $tenant): RedirectResponse
    {
        $days = (int) $request->validate(['days' => ['required', 'integer', 'min:1', 'max:365']])['days'];

        $base = $tenant->trial_ends_at?->isFuture() ? $tenant->trial_ends_at : now();

        $tenant->update([
            'status' => TenantStatus::Trial,
            'trial_ends_at' => $base->copy()->addDays($days),
            'suspended_at' => null,
            'suspended_reason' => null,
        ]);

        PlatformAuditLog::record('tenant.trial_extended', $tenant->id, "Trial extended by {$days} days");

        return back()->with('success', "Trial runs until {$tenant->trial_ends_at->format('d M Y')}.");
    }

    public function suspend(Request $request, Tenant $tenant): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:190']])['reason'] ?? null;

        $tenant->update([
            'status' => TenantStatus::Suspended,
            'suspended_at' => now(),
            'suspended_reason' => $reason,
        ]);

        PlatformAuditLog::record('tenant.suspended', $tenant->id, 'Workspace suspended', ['reason' => $reason]);

        return back()->with('success', 'Workspace suspended. Its users are locked out until you reactivate it.');
    }

    public function cancel(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status' => TenantStatus::Cancelled, 'suspended_at' => now(), 'suspended_reason' => null]);

        PlatformAuditLog::record('tenant.cancelled', $tenant->id, 'Workspace cancelled');

        return back()->with('success', 'Workspace cancelled. The data is kept.');
    }

    public function enter(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->session()->put('impersonating_tenant_id', $tenant->id);

        PlatformAuditLog::record('tenant.entered', $tenant->id, "Entered workspace {$tenant->name}");

        return redirect()->route('dashboard');
    }

    public function leave(Request $request): RedirectResponse
    {
        $tenantId = $request->session()->pull('impersonating_tenant_id');

        if ($tenantId) {
            PlatformAuditLog::record('tenant.exited', (int) $tenantId, 'Left the workspace');
        }

        return $tenantId
            ? redirect()->route('super-admin.tenants.show', $tenantId)
            : redirect()->route('super-admin.dashboard');
    }
}
