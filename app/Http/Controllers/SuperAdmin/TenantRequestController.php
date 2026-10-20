<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Mail\RequestRejectedMail;
use App\Models\Plan;
use App\Models\PlatformAuditLog;
use App\Models\TenantRequest;
use App\Services\Tenancy\TenantProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class TenantRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = RequestStatus::tryFrom((string) $request->query('status'));

        return view('super-admin.requests.index', [
            'requests' => TenantRequest::with('plan:id,name')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                    ->where('company_name', 'like', "%{$term}%")
                    ->orWhere('contact_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'status' => $status,
            'counts' => TenantRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(TenantRequest $tenantRequest): View
    {
        return view('super-admin.requests.show', [
            'req' => $tenantRequest->load(['plan', 'reviewer', 'tenant']),
            'plans' => Plan::query()->orderBy('sort_order')->get(['id', 'name', 'trial_days']),
        ]);
    }

    /** Create the workspace and its first admin from this request. */
    public function approve(Request $request, TenantRequest $tenantRequest, TenantProvisioner $provisioner): RedirectResponse
    {
        abort_if($tenantRequest->status === RequestStatus::Approved, 409, 'Already approved.');

        $data = $request->validate([
            'plan_id' => ['required', Rule::exists('plans', 'id')],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $provisioner->provision([
            'name' => $tenantRequest->company_name,
            'contact_name' => $tenantRequest->contact_name,
            'email' => $tenantRequest->email,
            'phone' => $tenantRequest->phone,
        ], Plan::findOrFail($data['plan_id']), $data['billing_cycle'], $tenantRequest);

        $tenantRequest->update(['admin_notes' => $data['admin_notes'] ?? $tenantRequest->admin_notes]);

        // The link is also emailed; showing it once lets the owner pass it on if mail is slow.
        return redirect()
            ->route('super-admin.tenants.show', $result['tenant'])
            ->with('success', "Workspace {$result['tenant']->name} created. A setup email was sent to {$result['admin']->email}.")
            ->with('setup_url', $result['setup_url']);
    }

    public function update(Request $request, TenantRequest $tenantRequest): RedirectResponse
    {
        abort_if($tenantRequest->status === RequestStatus::Approved, 409, 'An approved request can not be changed.');

        $data = $request->validate([
            'status' => ['required', Rule::in([RequestStatus::Rejected->value, RequestStatus::Pending->value])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $wasRejected = $tenantRequest->status === RequestStatus::Rejected;

        $tenantRequest->update($data + [
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($data['status'] === RequestStatus::Rejected->value && ! $wasRejected) {
            Mail::to($tenantRequest->email)->queue(new RequestRejectedMail($tenantRequest));
            PlatformAuditLog::record('request.rejected', null, "Rejected request from {$tenantRequest->company_name}");
        }

        return redirect()
            ->route('super-admin.requests.show', $tenantRequest)
            ->with('success', 'Request marked '.RequestStatus::from($data['status'])->label().'.');
    }

    public function destroy(TenantRequest $tenantRequest): RedirectResponse
    {
        $tenantRequest->delete();

        return redirect()->route('super-admin.requests.index')->with('success', 'Request deleted.');
    }
}
