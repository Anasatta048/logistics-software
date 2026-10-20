<?php

namespace App\Events;

use App\Enums\LoadStatus;
use App\Models\Load;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired on every status transition LoadStatusService makes (Booked→Loaded,
 * Loaded→Dispatched, Dispatched→Paid). LoadPaid is still fired separately for
 * the Paid transition specifically, since CalculateCommissionOnPaid only
 * cares about that one case.
 */
final readonly class LoadStatusChanged
{
    use Dispatchable;

    public function __construct(
        public Load $load,
        public User $changedBy,
        public LoadStatus $from,
        public LoadStatus $to,
    ) {
    }
}
