<?php

namespace App\Enums;

/**
 * Why a party's balance moved. The sign lives on the entry's amount; this says
 * which event wrote it, so the statement can be read without opening records.
 */
enum LedgerEntryType: string
{
    case CommissionAccrued = 'commission_accrued';
    case CommissionReversed = 'commission_reversed';
    case PaymentReceived = 'payment_received';
    case PaymentReversed = 'payment_reversed';
    case PayoutMade = 'payout_made';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::CommissionAccrued => 'Commission accrued',
            self::CommissionReversed => 'Commission reversed',
            self::PaymentReceived => 'Payment received',
            self::PaymentReversed => 'Payment corrected',
            self::PayoutMade => 'Payout made',
            self::Adjustment => 'Adjustment',
        };
    }

    /**
     * True for entries that create an amount to be settled against a load;
     * false for the money that settles it.
     */
    public function isAccrual(): bool
    {
        return in_array($this, [self::CommissionAccrued, self::CommissionReversed], true);
    }
}
