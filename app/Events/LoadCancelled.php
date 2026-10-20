<?php

namespace App\Events;

use App\Models\Load;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class LoadCancelled
{
    use Dispatchable;

    public function __construct(
        public Load $load,
        public User $cancelledBy,
        public string $reason,
    ) {
    }
}
