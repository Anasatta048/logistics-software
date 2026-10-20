<?php

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * A driver can hold several loads at once, so the status is the furthest point
 * any of them has reached (Booked → Pickup → Loaded) and falls back to
 * Available once every load is delivered. Inactive is the only manual state.
 */
enum DriverStatus: string
{
    case Available = 'available';
    case Booked = 'booked';
    case Pickup = 'pickup';
    case Loaded = 'loaded';
    case Inactive = 'inactive';

    public function label(): string
    {
        return Str::headline($this->value);
    }

    /** Busy with at least one load that is not delivered yet. */
    public function isOnLoad(): bool
    {
        return in_array($this, [self::Booked, self::Pickup, self::Loaded], true);
    }
}
