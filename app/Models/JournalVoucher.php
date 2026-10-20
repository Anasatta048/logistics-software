<?php

namespace App\Models;

use App\Enums\JournalVoucherStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Account $debitAccount
 * @property-read Account $creditAccount
 * @property-read User|null $approver
 * @property-read Load|null $load
 */
class JournalVoucher extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'jv_number',
        'date',
        'debit_account_id',
        'credit_account_id',
        'amount',
        'narration',
        'attachment_path',
        'approved_by',
        'status',
        'load_id',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'status' => JournalVoucherStatus::class,
        ];
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Named `loadRecord` because `Model::load()` already exists, so a `load()`
     * relation here is a fatal signature clash (same reason Expense does).
     */
    public function loadRecord(): BelongsTo
    {
        return $this->belongsTo(Load::class, 'load_id');
    }

    public function isPending(): bool
    {
        return $this->status === JournalVoucherStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === JournalVoucherStatus::Approved;
    }
}
