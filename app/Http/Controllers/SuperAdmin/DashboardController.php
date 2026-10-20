<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\TenantRequest;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(): View
    {
        return view('super-admin.dashboard', [
            'pending' => TenantRequest::where('status', RequestStatus::Pending)->count(),
            'approved' => TenantRequest::where('status', RequestStatus::Approved)->count(),
            'rejected' => TenantRequest::where('status', RequestStatus::Rejected)->count(),
            'plansActive' => Plan::where('is_active', true)->count(),
            'latest' => TenantRequest::with('plan')->latest()->limit(6)->get(),
            'planDemand' => Plan::withCount('requests')->orderBy('sort_order')->get(),
        ]);
    }
}
