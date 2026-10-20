<?php

namespace App\Enums;

enum TenantStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Trial => 'bg-brand-gold/15 text-brand-gold',
            self::Active => 'bg-status-paid/15 text-status-paid',
            self::Suspended => 'bg-warning/15 text-warning',
            self::Cancelled => 'bg-status-cancelled/15 text-status-cancelled',
        };
    }
}
