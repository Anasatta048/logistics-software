<?php

namespace App\Listeners;

use App\Events\LoadCancelled;
use App\Services\Account\JournalVoucherService;

final readonly class VoidFinancialEntriesOnCancellation
{
    public function __construct(private JournalVoucherService $journalVouchers)
    {
    }

    /**
     * Cancelled loads must keep their audit trail, so any journal voucher tied
     * to this load is voided — its postings reversed with an opposite entry —
     * never deleted (SRD 12 / Phase 7 DoD).
     */
    public function handle(LoadCancelled $event): void
    {
        $this->journalVouchers->voidForLoad($event->load, $event->cancelledBy);
    }
}
