<?php

namespace App\Models;

use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Account $account
 * @property-read User|null $author
 */
class AccountTransaction extends Model
{
    use BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'type',
        'amount',
        'running_balance',
        'occurred_on',
        'source_type',
        'source_id',
        'description',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'source_type' => TransactionSource::class,
            'amount' => 'decimal:2',
            'running_balance' => 'decimal:2',
            'occurred_on' => 'date',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
