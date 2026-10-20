<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum AttachmentType: string
{
    case RateConfirmation = 'rate_confirmation';
    case Bol = 'bol';
    case Pod = 'pod';
    case Other = 'other';

    public function label(): string
    {
        return Str::headline($this->value);
    }
}
