<?php

namespace App\Events;

use App\Models\Load;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Phase 9: there is no dedicated "booked" transition in LoadStatusService — a
 * new load is simply created with LoadStatus::Booked — so this is fired
 * directly from LoadService::create() as the most sensible integration point
 * for a "load booked" notification.
 */
final readonly class LoadBooked
{
    use Dispatchable;

    public function __construct(
        public Load $load,
        public User $bookedBy,
    ) {
    }
}
