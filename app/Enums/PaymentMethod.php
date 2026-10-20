<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Cheque = 'cheque';
    case Online = 'online';
    case Other = 'other';

    public function label(): string
    {
        return Str::headline($this->value);
    }
}
