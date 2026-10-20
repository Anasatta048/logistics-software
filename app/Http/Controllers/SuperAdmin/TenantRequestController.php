<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\TenantRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class TenantRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = RequestStatus::tryFrom((string) $request->query('status'));

        return view('super-admin.requests.index', [
            'requests' => TenantRequest::with('plan')
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
            'req' => $tenantRequest->load(['plan', 'reviewer']),
        ]);
    }

    public function update(Request $request, TenantRequest $tenantRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_column(RequestStatus::cases(), 'value'))],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tenantRequest->update($data + [
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

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
