<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum JournalVoucherStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Voided = 'voided';

    public function label(): string
    {
        return Str::headline($this->value);
    }
}
