<?php

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * The double-entry side of a movement, from the point of view of the account it
 * was posted to. An account is an asset, so a Debit puts money in and a Credit
 * takes money out — which is why paying an expense posts a credit here, even
 * though the expense itself is a debit in the books.
 */
enum TransactionType: string
{
    case Debit = 'debit';
    case Credit = 'credit';

    public function label(): string
    {
        return Str::headline($this->value);
    }

    /**
     * Signed effect on the account balance.
     */
    public function sign(): int
    {
        return $this === self::Debit ? 1 : -1;
    }

    public function opposite(): self
    {
        return $this === self::Debit ? self::Credit : self::Debit;
    }
}
