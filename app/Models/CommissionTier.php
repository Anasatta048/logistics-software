<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** One slab of the dispatcher commission ladder. */
class CommissionTier extends Model
{
    use BelongsToTenant;

    protected $fillable = ['from_amount', 'rate'];

    protected function casts(): array
    {
        return ['from_amount' => 'decimal:2', 'rate' => 'decimal:2'];
    }
}
