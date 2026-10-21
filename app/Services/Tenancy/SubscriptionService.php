<?php

namespace App\Services\Tenancy;

use App\Enums\TenantStatus;
use App\Mail\SubscriptionPaymentReviewedMail;
use App\Mail\SubscriptionPaymentSubmittedMail;
use App\Models\Plan;
use App\Models\PlatformAuditLog;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Turns money into subscription time. A workspace reports a payment (with the
 * transfer slip), the platform owner approves it, and only approval moves the
 * plan and the paid-until date. Platform-recorded payments skip the queue.
 */
final class SubscriptionService
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly TenantLimits $limits,
        private readonly TenantProvisioner $provisioner,
    ) {
    }

    /** Payment methods the platform has set up details for (config/saas.php). */
    public static function methods(): array
    {
        $b = config('saas.billing');

        $configured = array_filter([
            'bank_transfer' => ($b['account_number'] ?? null) || ($b['iban'] ?? null),
            'raast' => $b['raast_id'] ?? null,
            'jazzcash' => $b['jazzcash_number'] ?? null,
            'easypaisa' => $b['easypaisa_number'] ?? null,
        ]);

        return array_intersect_key(SubscriptionPayment::METHODS, $configured);
    }

    /** A workspace reports a payment it made; it waits for the platform owner. */
    public function submit(Tenant $tenant, Plan $plan, string $cycle, string $method, ?string $reference, ?UploadedFile $proof, ?string $note, User $by): SubscriptionPayment
    {
        // The plan being paid for has to hold what the workspace already has.
        $over = $this->context->run($tenant, fn (): array => $this->limits->exceededBy($plan));

        if ($over !== []) {
            throw ValidationException::withMessages([
                'plan_id' => 'You already use '.implode(', ', $over).'. Pick a bigger plan or reduce your usage first.',
            ]);
        }

        $charge = $plan->charge($cycle);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $cycle,
            'amount' => $charge['amount'],
            'currency' => $charge['currency'],
            'method' => $method,
            'reference' => $reference,
            'note' => $note,
            'proof_path' => $proof?->store("billing-proofs/{$tenant->id}", 'local'),
            'status' => 'pending',
            'submitted_by' => $by->id,
        ]);

        PlatformAuditLog::record('payment.submitted', $tenant->id, "{$tenant->name} reported {$payment->money()} via {$payment->methodLabel()}", ['payment_id' => $payment->id]);

        if ($alertTo = config('saas.notify_email') ?: User::withoutGlobalScopes()->where('is_super_admin', true)->value('email')) {
            Mail::to($alertTo)->queue(new SubscriptionPaymentSubmittedMail($payment));
        }

        return $payment;
    }

    /** Approve a reported payment: switch the plan if it changed and extend the paid period. */
    public function approve(SubscriptionPayment $payment, User $reviewer): SubscriptionPayment
    {
        abort_unless($payment->isPending(), 409, 'This payment has already been reviewed.');

        return DB::transaction(function () use ($payment, $reviewer): SubscriptionPayment {
            $tenant = Tenant::query()->lockForUpdate()->findOrFail($payment->tenant_id);
            $plan = $payment->plan ?? $tenant->plan;

            if ($plan && $plan->id !== $tenant->plan_id) {
                $over = $this->context->run($tenant, fn (): array => $this->limits->exceededBy($plan));

                if ($over !== []) {
                    throw ValidationException::withMessages(['payment' => "Can't switch to {$plan->name}: the workspace already uses ".implode(', ', $over).'.']);
                }
            }

            [$start, $end] = $this->extend($tenant, $payment->billing_cycle, $plan);

            $payment->update([
                'status' => 'approved',
                'receipt_number' => $this->receiptNumber($payment),
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
            ]);

            PlatformAuditLog::record('payment.approved', $tenant->id, "Approved {$payment->money()} ({$payment->receipt_number})", ['payment_id' => $payment->id, 'until' => $end->toDateString()]);

            Mail::to($tenant->email)->queue(new SubscriptionPaymentReviewedMail($payment->fresh(['tenant', 'plan'])));

            return $payment;
        });
    }

    public function reject(SubscriptionPayment $payment, User $reviewer, ?string $reason): SubscriptionPayment
    {
        abort_unless($payment->isPending(), 409, 'This payment has already been reviewed.');

        $payment->update(['status' => 'rejected', 'review_note' => $reason, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now()]);

        PlatformAuditLog::record('payment.rejected', $payment->tenant_id, "Rejected {$payment->money()}", ['payment_id' => $payment->id, 'reason' => $reason]);

        Mail::to($payment->tenant->email)->queue(new SubscriptionPaymentReviewedMail($payment->fresh(['tenant', 'plan'])));

        return $payment;
    }

    /** The platform owner received money outside the queue (cash, other channel): log it and extend. */
    public function recordManual(Tenant $tenant, User $by, ?string $note = null): SubscriptionPayment
    {
        $plan = $tenant->plan;
        $charge = $plan?->charge($tenant->billing_cycle) ?? ['currency' => 'USD', 'amount' => 0.0];

        [$start, $end] = $this->extend($tenant, $tenant->billing_cycle, $plan);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan?->id,
            'billing_cycle' => $tenant->billing_cycle,
            'amount' => $charge['amount'],
            'currency' => $charge['currency'],
            'method' => 'manual',
            'note' => $note,
            'status' => 'approved',
            'submitted_by' => $by->id,
            'reviewed_by' => $by->id,
            'reviewed_at' => now(),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
        ]);
        $payment->update(['receipt_number' => $this->receiptNumber($payment)]);

        PlatformAuditLog::record('tenant.activated', $tenant->id, 'Payment recorded; subscription extended', ['until' => $end->toDateString(), 'receipt' => $payment->receipt_number]);

        return $payment;
    }

    /**
     * Activate the workspace and push the paid-until date one cycle on (from
     * today, or from the current end when renewing early).
     *
     * @return array{0: \Carbon\CarbonInterface, 1: \Carbon\CarbonInterface}
     */
    private function extend(Tenant $tenant, string $cycle, ?Plan $plan): array
    {
        $running = $tenant->status === TenantStatus::Active && $tenant->subscription_ends_at?->isFuture();
        $start = $running ? $tenant->subscription_ends_at : now();
        $end = $this->provisioner->periodEnd($start, $cycle);

        $tenant->update([
            'plan_id' => $plan?->id ?? $tenant->plan_id,
            'billing_cycle' => $cycle,
            'status' => TenantStatus::Active,
            'subscription_ends_at' => $end,
            'suspended_at' => null,
            'suspended_reason' => null,
            'reminder_stage' => null,
            'reminder_for' => null,
        ]);

        return [$start, $end];
    }

    private function receiptNumber(SubscriptionPayment $payment): string
    {
        return 'SUB-'.now()->format('Y').'-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT);
    }
}
