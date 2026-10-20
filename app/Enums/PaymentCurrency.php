<?php

namespace App\Enums;

enum PaymentCurrency: string
{
    // Companies pay in USD only; PKR is what the conversion produces, not a billing currency.
    case Usd = 'usd';

    public function label(): string
    {
        return strtoupper($this->value);
    }
}
