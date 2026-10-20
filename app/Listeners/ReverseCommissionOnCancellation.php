<?php

namespace App\Listeners;

use App\Events\LoadCancelled;
use App\Services\Commission\CommissionService;

final readonly class ReverseCommissionOnCancellation
{
    public function __construct(private CommissionService $commissions)
    {
    }

    /**
     * SRD 6.3: a cancelled load must not leave a payable commission behind.
     *
     * ponytail: a no-op until a *paid* load can be cancelled or voided
     * (`LoadStatus::Paid` is final today, so cancelling a booked/loaded/
     * dispatched load has nothing to reverse). reverseForLoad() is already the
     * single choke point, so the reversal switches on the day a void path lands
     * without touching this listener.
     */
    public function handle(LoadCancelled $event): void
    {
        $this->commissions->reverseForLoad($event->load, $event->cancelledBy);
    }
}
