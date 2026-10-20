<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Money moved between two of the company's own accounts (cash to bank, bank to bank...). */
class AccountTransfer extends Model
{
    use BelongsToTenant;

    protected $fillable = ['from_account_id', 'to_account_id', 'amount', 'fee', 'transfer_date', 'reference', 'narration', 'created_by', 'reversed_at', 'reversed_by'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'transfer_date' => 'date',
            'reversed_at' => 'datetime',
        ];
    }

    public function isReversed(): bool
    {
        return $this->reversed_at !== null;
    }

    /** @return BelongsTo<Account, $this> */
    public function from(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id')->withTrashed();
    }

    /** @return BelongsTo<Account, $this> */
    public function to(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id')->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
