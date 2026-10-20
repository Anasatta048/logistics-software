<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum LoadStatus: string
{
    case Booked = 'booked';
    case Pickup = 'pickup';
    case Loaded = 'loaded';
    case Delivered = 'delivered';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return Str::headline($this->value);
    }

    /**
     * The next status on the happy path, or null once the load is final.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Booked => self::Pickup,
            self::Pickup => self::Loaded,
            self::Loaded => self::Delivered,
            self::Delivered => self::Paid,
            self::Paid, self::Cancelled => null,
        };
    }

    /**
     * The happy path is a straight line; Cancelled is reachable from any
     * non-final state (SRD 6.3: booked, pickup, loaded or delivered).
     */
    public function canTransitionTo(self $next): bool
    {
        if ($next === self::Cancelled) {
            return ! $this->isFinal();
        }

        // Booked, Pickup, Loaded and Delivered are set by hand and may go in any
        // direction; only Paid is a one-way step (from Delivered).
        if ($this->isManual() && $next->isManual()) {
            return $next !== $this;
        }

        return $next === $this->next();
    }

    /** The four statuses a dispatcher or admin sets by hand. */
    public function isManual(): bool
    {
        return in_array($this, [self::Booked, self::Pickup, self::Loaded, self::Delivered], true);
    }

    /** Still holds the driver: booked, picked up or loaded, not yet delivered. */
    public function isActive(): bool
    {
        return in_array($this, [self::Booked, self::Pickup, self::Loaded], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Paid, self::Cancelled], true);
    }
}
