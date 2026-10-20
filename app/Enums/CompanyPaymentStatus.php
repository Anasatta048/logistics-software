<?php

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * `partial` is derived, never typed: the payment flips to it as soon as
 * amount_received falls short of total_due, and back to FullySettled if the
 * balance arrives later (FLOW_UPDATE_PLAN Section 5.2).
 */
enum CompanyPaymentStatus: string
{
    case Pending = 'pending';
    case FullySettled = 'fully_settled';
    case PartiallySettled = 'partially_settled';

    public function label(): string
    {
        return Str::headline($this->value);
    }

    public static function forAmounts(float $due, float $received): self
    {
        return match (true) {
            // round() first: a company that pays the last cent of a rounded
            // total must not land in "partially settled" on float noise.
            round($received, 2) >= round($due, 2) => self::FullySettled,
            $received > 0 => self::PartiallySettled,
            default => self::Pending,
        };
    }
}
