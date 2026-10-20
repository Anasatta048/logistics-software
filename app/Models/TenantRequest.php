<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRequest extends Model
{
    protected $fillable = [
        'company_name', 'contact_name', 'email', 'phone', 'fleet_size', 'plan_id',
        'message', 'status', 'admin_notes', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
