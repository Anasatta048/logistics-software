<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum AccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';

    public function label(): string
    {
        return Str::headline($this->value);
    }
}
