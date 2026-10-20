<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum TaxType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return Str::headline($this->value);
    }
}
