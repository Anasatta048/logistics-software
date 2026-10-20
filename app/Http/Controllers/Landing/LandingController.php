<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landing\StoreTenantRequestRequest;
use App\Mail\NewRequestAdminMail;
use App\Mail\RequestReceivedMail;
use App\Models\Plan;
use App\Models\TenantRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
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
        $tenantRequest = TenantRequest::create($request->validated());

        // Queued: the visitor never waits on the mail server.
        Mail::to($tenantRequest->email)->queue(new RequestReceivedMail($tenantRequest));

        $alertTo = config('saas.notify_email')
            ?: User::withoutGlobalScopes()->where('is_super_admin', true)->value('email');

        if ($alertTo) {
            Mail::to($alertTo)->queue(new NewRequestAdminMail($tenantRequest));
        }

        return redirect()
            ->to(route('home').'#request')
            ->with('request_sent', true);
    }
}
