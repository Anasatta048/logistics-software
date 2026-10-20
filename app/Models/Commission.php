<?php

namespace App\Models;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The dispatcher's own cut. `margin_amount` is the base it was computed from,
 * which since FLOW_UPDATE_PLAN Section 4 is the load's Company Commission
 * Amount — what Dreamelect earns — rather than rate minus driver pay.
 *
 * @property-read Load $loadRecord
 * @property-read User $dispatcher
 * @property-read Account|null $paymentAccount
 * @property-read User|null $paidByUser
 */
class Commission extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'load_id',
        'dispatcher_id',
        'margin_amount',
        'commission_type',
        'commission_percentage',
        'commission_fixed_amount',
        'commission_amount',
        'status',
        'calculated_at',
        'reversed_at',
        'conversion_rate',
        'pkr_amount',
        'paid_at',
        'payment_account_id',
        'paid_by',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'margin_amount' => 'decimal:2',
            'commission_type' => CommissionType::class,
            'commission_percentage' => 'decimal:2',
            'commission_fixed_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'status' => CommissionStatus::class,
            'calculated_at' => 'datetime',
            'reversed_at' => 'datetime',
            'conversion_rate' => 'decimal:4',
            'pkr_amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    /**
     * Named `loadRecord` because `Model::load()` already exists, so a
     * `load()` relation here is a fatal signature clash (same reason
     * LoadStatusHistory/LoadAttachment have no back-reference to the load).
     */
    public function loadRecord(): BelongsTo
    {
        return $this->belongsTo(Load::class, 'load_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Only an untouched accrual can be paid: a reversal has nothing left to
     * hand over, and a paid row must never pay twice.
     */
    public function isPayable(): bool
    {
        return $this->status === CommissionStatus::Accrued
            && (float) $this->commission_amount > 0;
    }
}
