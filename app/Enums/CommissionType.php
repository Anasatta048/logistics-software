<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum CommissionType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return Str::headline($this->value);
    }
}
