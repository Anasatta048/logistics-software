<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User $enteredByUser
 * @property-read Account|null $account
 * @property-read ExpenseCategory|null $category
 */
class Expense extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\ExpenseFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'expense_date',
        'expense_category_id',
        'amount',
        'payee',
        'reference',
        'payment_method',
        'account_id',
        'attachment_path',
        'entered_by',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'payment_method' => \App\Enums\PaymentMethod::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function enteredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
