<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landing\StoreTenantRequestRequest;
use App\Models\Plan;
use App\Models\TenantRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class LandingController extends Controller
{
    public function index(): View
    {
        return view('landing.index', [
            'plans' => Plan::active()->get(),
        ]);
    }

    public function store(StoreTenantRequestRequest $request): RedirectResponse
    {
        TenantRequest::create($request->validated());

        return redirect()
            ->to(route('home').'#request')
            ->with('request_sent', true);
    }
}
