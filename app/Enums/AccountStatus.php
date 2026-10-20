<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum AccountStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return Str::headline($this->value);
    }
}
