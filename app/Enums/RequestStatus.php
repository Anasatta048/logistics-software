<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'bg-brand-gold/15 text-brand-gold',
            self::Approved => 'bg-status-paid/15 text-status-paid',
            self::Rejected => 'bg-status-cancelled/15 text-status-cancelled',
        };
    }
}
