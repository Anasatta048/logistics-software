<?php

namespace App\Services\Load;

use App\Enums\LoadStatus;
use App\Events\LoadCancelled;
use App\Models\Load;
use App\Models\User;
use App\Services\Company\CompanyPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cancelling a load reverses what it accrued. The company commission is
 * reversed on the load row itself, in the same transaction as the status
 * change; the dispatcher's separate Commission row is reversed by its listener.
 */
final readonly class LoadCancellationService
{
    public function __construct(
        private LoadStatusService $statusService,
        private CompanyPaymentService $payments,
    ) {
    }

    /**
     * Allowed from booked, pickup, loaded or delivered — never once paid (SRD 6.3).
     * After this the load is locked because `LoadStatus::Cancelled` is final.
     *
     * A load whose commission already went into a received payment cannot be
     * quietly reversed — that money physically arrived. It needs
     * `adjustSettlement`, which is admin-only and books a negative line for the
     * next period instead of editing the receipt (FLOW_UPDATE_PLAN 5.4).
     *
     * Because only a Paid load can be settled, that one exception is what
     * makes the adjustment path reachable at all: without it a settled load
     * could never be cancelled and the credit line could never be written.
     *
     * @throws ValidationException
     */
    public function cancel(Load $load, User $actor, string $reason, bool $adjustSettlement = false): Load
    {
        $from = $load->status;
        $correctingSettledLoad = $from === LoadStatus::Paid && $adjustSettlement;

        // Checked before the status guard so a settled load gets the actionable
        // message (which payment to look at) instead of a flat "cannot cancel".
        // The payments table is the authority on what has been received.
        $settledIn = $this->payments->settledIn($load);

        if ($settledIn !== null && ! $adjustSettlement) {
            throw ValidationException::withMessages([
                'status' => "This load's commission was already included in Payment #{$settledIn->id} "
                    ."dated {$settledIn->payment_date->toDateString()}. Re-submit with settlement adjustment "
                    .'to cancel it and carry a credit into the next period.',
            ]);
        }

        if (! $correctingSettledLoad && ! $from->canTransitionTo(LoadStatus::Cancelled)) {
            throw ValidationException::withMessages([
                'status' => "A {$from->label()} load cannot be cancelled.",
            ]);
        }

        DB::transaction(function () use ($load, $from, $actor, $reason, $settledIn): void {
            // Booked before the amount is zeroed below, so the credit matches
            // what the company actually paid.
            if ($settledIn !== null) {
                $this->payments->createAdjustment($load, $actor);
            }

            $load->update([
                'status' => LoadStatus::Cancelled,
                'company_commission_amount' => 0,
                'cancellation_reason' => $reason,
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
            ]);

            $this->statusService->record($load, $from, LoadStatus::Cancelled, $actor, $reason);

            LoadCancelled::dispatch($load, $actor, $reason);
        });

        return $load->refresh();
    }
}
