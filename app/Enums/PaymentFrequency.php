<?php

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * A dropdown rather than a hard-coded "weekly" because a carrier can move to
 * bi-weekly or monthly invoicing; the settlement form reads it to default the
 * period it offers (FLOW_UPDATE_PLAN Section 3.1).
 */
enum PaymentFrequency: string
{
    case Weekly = 'weekly';
    case BiWeekly = 'bi_weekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return Str::headline($this->value);
    }

    public function days(): int
    {
        return match ($this) {
            self::Weekly => 7,
            self::BiWeekly => 14,
            self::Monthly => 30,
        };
    }
}
