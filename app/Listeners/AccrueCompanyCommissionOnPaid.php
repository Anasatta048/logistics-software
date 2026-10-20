<?php

namespace App\Listeners;

use App\Enums\LedgerEntryType;
use App\Events\LoadPaid;
use App\Services\Ledger\LedgerService;
use App\Support\Timezones;

final readonly class AccrueCompanyCommissionOnPaid
{
    public function __construct(private LedgerService $ledger)
    {
    }

    /**
     * A load marked Paid makes its company commission receivable: the company
     * now owes Dreamelect that amount, and it stays on the company's ledger
     * until a payment settles it.
     */
    public function handle(LoadPaid $event): void
    {
        $load = $event->load;
        $company = $load->company;
        $amount = (float) $load->company_commission_amount;

        // Replaying the event must not bill the company twice.
        if ($company === null || $amount <= 0 || $this->ledger->accruedForLoad($company, $load) > 0) {
            return;
        }

        $this->ledger->post(
            $company,
            LedgerEntryType::CommissionAccrued,
            $amount,
            sprintf(
                'Commission on %s: $%s rate × %s%%',
                $load->reference_number,
                number_format((float) $load->rate, 2),
                rtrim(rtrim((string) $load->company_commission_percentage, '0'), '.'),
            ),
            source: $load,
            load: $load,
            actor: $event->paidBy,
            occurredOn: Timezones::today(),
        );
    }
}
