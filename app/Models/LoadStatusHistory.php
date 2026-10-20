<?php

namespace App\Models;

use App\Enums\LoadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User $changedBy
 */
class LoadStatusHistory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'load_id',
        'from_status',
        'to_status',
        'changed_by',
        'reason',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'from_status' => LoadStatus::class,
            'to_status' => LoadStatus::class,
        ];
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
