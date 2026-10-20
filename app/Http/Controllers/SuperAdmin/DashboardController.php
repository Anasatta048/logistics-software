<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\RequestStatus;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Models\TenantRequest;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(): View
    {
        // A handful of grouped queries, whatever the number of tenants.
        $requestCounts = TenantRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $tenantCounts = Tenant::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $mrr = Tenant::query()
            ->where('status', TenantStatus::Active)
            ->join('plans', 'plans.id', '=', 'tenants.plan_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN tenants.billing_cycle = 'yearly' THEN plans.yearly_price / 12 ELSE plans.monthly_price END), 0) as mrr")
            ->value('mrr');

        $approved = (int) ($requestCounts[RequestStatus::Approved->value] ?? 0);
        $decided = $approved + (int) ($requestCounts[RequestStatus::Rejected->value] ?? 0);
        $paying = (int) ($tenantCounts[TenantStatus::Active->value] ?? 0);
        $everTrialed = (int) $tenantCounts->sum();

        return view('super-admin.dashboard', [
            'pending' => (int) ($requestCounts[RequestStatus::Pending->value] ?? 0),
            'approved' => $approved,
            'rejected' => (int) ($requestCounts[RequestStatus::Rejected->value] ?? 0),
            'plansActive' => Plan::where('is_active', true)->count(),
            'tenantCounts' => $tenantCounts,
            'mrr' => round((float) $mrr, 2),
            'newTenants' => Tenant::where('created_at', '>=', now()->subDays(30))->count(),
            'conversion' => $everTrialed > 0 ? round($paying / $everTrialed * 100) : 0,
            'approvalRate' => $decided > 0 ? round($approved / $decided * 100) : 0,
            'endingSoon' => Tenant::query()
                ->whereIn('status', [TenantStatus::Trial, TenantStatus::Active])
                ->where(fn ($q) => $q
                    ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
                    ->orWhereBetween('subscription_ends_at', [now(), now()->addDays(7)]))
                ->orderByRaw('COALESCE(trial_ends_at, subscription_ends_at)')
                ->limit(6)
                ->get(['id', 'name', 'status', 'trial_ends_at', 'subscription_ends_at']),
            'latest' => TenantRequest::with('plan:id,name')->latest()->limit(6)->get(),
            'planDemand' => Plan::withCount(['requests', 'tenants'])->orderBy('sort_order')->get(),
            'audit' => PlatformAuditLog::with('user:id,name')->latest('created_at')->limit(8)->get(),
        ]);
    }
}
