<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Services\Tenancy\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The queue of payments workspaces reported: check the slip, then approve or reject. */
final class PaymentController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions)
    {
    }

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true) ? $request->query('status') : null;

        return view('super-admin.payments.index', [
            'payments' => SubscriptionPayment::query()
                ->with(['tenant:id,name,email', 'plan:id,name'])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                    ->where('reference', 'like', "%{$term}%")
                    ->orWhere('receipt_number', 'like', "%{$term}%")
                    ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$term}%"))))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'counts' => SubscriptionPayment::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function proof(SubscriptionPayment $payment): StreamedResponse
    {
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->download($payment->proof_path);
    }

    public function approve(Request $request, SubscriptionPayment $payment): RedirectResponse
    {
        $this->subscriptions->approve($payment, $request->user());

        return back()->with('success', "Approved. {$payment->tenant->name} is active until {$payment->fresh()->period_end->format('d M Y')}.");
    }

    public function reject(Request $request, SubscriptionPayment $payment): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:500']])['reason'];

        $this->subscriptions->reject($payment, $request->user(), $reason);

        return back()->with('success', 'Payment rejected and the workspace was told why.');
    }
}
