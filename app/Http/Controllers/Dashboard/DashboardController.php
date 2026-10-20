<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\BusinessDashboardService;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly BusinessDashboardService $business,
    ) {
    }

    public function index(Request $request): View
    {
        return view('dashboard', [
            'metrics' => $this->dashboard->forUser($request->user()),
            'business' => $this->business->forUser($request->user(), $request->boolean('fresh')),
        ]);
    }
}
