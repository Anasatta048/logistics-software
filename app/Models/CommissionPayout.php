<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Money handed to a dispatcher against their commission, in any amount: a part
 * payment, the exact balance, or an advance ahead of what they have earned. The
 * USD figure settles their ledger; the PKR figure (USD × the rate typed on the
 * form) is what left the paying account.
 *
 * @property-read User $dispatcher
 * @property-read Account $paymentAccount
 * @property-read User|null $paidByUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Commission> $commissions
 */
class CommissionPayout extends Model
{
    protected $fillable = [
        'dispatcher_id', 'usd_amount', 'conversion_rate', 'pkr_amount', 'payment_method',
        'payment_account_id', 'paid_at', 'reference', 'notes', 'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'usd_amount' => 'decimal:2',
            'conversion_rate' => 'decimal:4',
            'pkr_amount' => 'decimal:2',
            'payment_method' => \App\Enums\PaymentMethod::class,
            'paid_at' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    /** @return BelongsTo<Account, $this> */
    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    /** @return BelongsTo<User, $this> */
    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /** @return BelongsToMany<Commission, $this> */
    public function commissions(): BelongsToMany
    {
        return $this->belongsToMany(Commission::class, 'commission_payout_allocations')->withPivot('amount');
    }
}
