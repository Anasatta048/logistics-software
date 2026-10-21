<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\SubmitPaymentRequest;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Services\Tenancy\SubscriptionService;
use App\Support\Tenancy\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A workspace's own subscription: what it pays, what it reported, its receipts.
 * Reachable while the workspace is locked (see ResolveTenant) so an expired
 * customer can pay their way back in.
 */
final class BillingController extends Controller
{
    public function __construct(private readonly TenantContext $context, private readonly SubscriptionService $subscriptions)
    {
    }

    public function index(): View
    {
        $tenant = $this->context->tenant()->load('plan');

        return view('billing.index', [
            'tenant' => $tenant,
            'plans' => Plan::active()->get(),
            'payments' => SubscriptionPayment::query()->with('plan')->where('tenant_id', $tenant->id)->latest()->limit(50)->get(),
            'methods' => SubscriptionService::methods(),
            'billing' => config('saas.billing'),
        ]);
    }

    public function store(SubmitPaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->subscriptions->submit(
            $this->context->tenant(),
            Plan::query()->findOrFail($data['plan_id']),
            $data['billing_cycle'],
            $data['method'],
            $data['reference'] ?? null,
            $request->file('proof'),
            $data['note'] ?? null,
            $request->user(),
        );

        return redirect()->route('billing.index')->with('success', 'Payment reported. We will confirm it within one business day and email you.');
    }

    public function proof(SubscriptionPayment $payment): StreamedResponse
    {
        $this->own($payment);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->download($payment->proof_path);
    }

    public function receipt(SubscriptionPayment $payment): Response
    {
        $this->own($payment);
        abort_unless($payment->isApproved(), 404);

        $payment->load(['tenant', 'plan']);

        return Pdf::loadView('billing.receipt', ['payment' => $payment, 'legal' => config('saas.legal')])
            ->download("{$payment->receipt_number}.pdf");
    }

    private function own(SubscriptionPayment $payment): void
    {
        abort_unless($payment->tenant_id === $this->context->id(), 404);
    }
}
