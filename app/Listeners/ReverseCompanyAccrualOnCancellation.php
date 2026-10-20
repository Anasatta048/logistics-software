<?php

namespace App\Listeners;

use App\Enums\LedgerEntryType;
use App\Events\LoadCancelled;
use App\Services\Ledger\LedgerService;
use App\Support\Timezones;

final readonly class ReverseCompanyAccrualOnCancellation
{
    public function __construct(private LedgerService $ledger)
    {
    }

    /**
     * Takes back exactly what was billed for this load. If the company had
     * already paid it, the balance simply goes the other way and shows as an
     * advance the next receipt will use up — the original payment is untouched.
     */
    public function handle(LoadCancelled $event): void
    {
        $load = $event->load;
        $company = $load->company;

        if ($company === null) {
            return;
        }

        $accrued = $this->ledger->accruedForLoad($company, $load);

        if ($accrued <= 0) {
            return;
        }

        $this->ledger->post(
            $company,
            LedgerEntryType::CommissionReversed,
            -$accrued,
            "{$load->reference_number} cancelled — commission reversed: {$event->reason}",
            source: $load,
            load: $load,
            actor: $event->cancelledBy,
            occurredOn: Timezones::today(),
        );
    }
}
