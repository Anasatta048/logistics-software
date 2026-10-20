<?php

namespace App\Models;

use App\Enums\LedgerEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One immutable line of a party's USD ledger. See the migration for the sign
 * convention. Editing or deleting a row would rewrite history, so both throw.
 *
 * @property-read Load|null $loadRecord
 * @property-read User|null $creator
 */
class LedgerEntry extends Model
{
    public const COMPANY = 'company';

    public const DISPATCHER = 'dispatcher';

    protected $table = 'party_ledger_entries';

    protected $fillable = [
        'party_type', 'party_id', 'entry_type', 'amount', 'balance_after', 'load_id',
        'source_type', 'source_id', 'description', 'occurred_on', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_type' => LedgerEntryType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'occurred_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Ledger entries are append-only.'));
        static::deleting(fn () => throw new LogicException('Ledger entries are append-only.'));
    }

    /** @return BelongsTo<Load, $this> */
    public function loadRecord(): BelongsTo
    {
        return $this->belongsTo(Load::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Statement column. A company owes us, so growth of its balance is a debit;
     * we owe a dispatcher, so growth of theirs is a credit.
     */
    public function column(): string
    {
        $increases = (float) $this->amount > 0;

        return ($this->party_type === self::COMPANY) === $increases ? 'debit' : 'credit';
    }
}
