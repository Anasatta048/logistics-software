<?php

namespace App\Listeners;

use App\Events\LoadPaid;
use App\Services\Commission\CommissionService;

final readonly class CalculateCommissionOnPaid
{
    public function __construct(private CommissionService $commissions)
    {
    }

    /**
     * SRD 6.3: the dispatcher earns their cut the moment the load is paid, so
     * nobody has to remember to calculate it by hand.
     */
    public function handle(LoadPaid $event): void
    {
        $this->commissions->calculateForLoad($event->load, $event->paidBy);
    }
}
