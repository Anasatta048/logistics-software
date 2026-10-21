<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A subscription payment a workspace reports (bank transfer, Raast, wallet) or
 * the platform owner records by hand. Like Tenant it is NOT tenant-scoped: the
 * super admin reviews everyone's, so tenant screens must filter by tenant_id.
 */
class SubscriptionPayment extends Model
{
    public const METHODS = [
        'bank_transfer' => 'Bank transfer',
        'raast' => 'Raast',
        'jazzcash' => 'JazzCash',
        'easypaisa' => 'Easypaisa',
        'manual' => 'Recorded by platform',
    ];

    protected $fillable = [
        'tenant_id', 'plan_id', 'billing_cycle', 'amount', 'currency', 'method', 'reference', 'proof_path', 'note',
        'status', 'receipt_number', 'review_note', 'submitted_by', 'reviewed_by', 'reviewed_at', 'period_start', 'period_end',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by')->withoutGlobalScopes();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? ucfirst($this->method);
    }

    public function money(): string
    {
        return ($this->currency === 'PKR' ? 'Rs ' : '$').number_format((float) $this->amount, $this->currency === 'PKR' ? 0 : 2);
    }
}
