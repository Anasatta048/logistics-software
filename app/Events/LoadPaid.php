<?php

namespace App\Events;

use App\Models\Load;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class LoadPaid
{
    use Dispatchable;

    public function __construct(
        public Load $load,
        public User $paidBy,
    ) {
    }
}
