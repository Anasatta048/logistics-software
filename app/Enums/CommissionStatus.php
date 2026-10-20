<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum CommissionStatus: string
{
    case Accrued = 'accrued';
    case Paid = 'paid';
    case Reversed = 'reversed';

    public function label(): string
    {
        return Str::headline($this->value);
    }
}
