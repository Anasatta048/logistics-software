<?php

namespace App\Models;

use App\Enums\CompanyPaymentStatus;
use App\Enums\TaxType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One weekly USD receipt from a company, covering the commission on a batch of
 * loads (FLOW_UPDATE_PLAN Section 5). Always USD: a company settles in the
 * currency it was billed in. The PKR side (rate, tax, deposit account) sits on
 * the same record once the money is converted, in the same form or later.
 *
 * @property-read Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Load> $loads
 * @property-read Account|null $depositAccount
 * @property-read User|null $creator
 */
class CompanyPayment extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyPaymentFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'invoice_id',
        'period_from',
        'period_to',
        'total_due',
        'amount_received',
        'advance_applied',
        'payment_date',
        'payment_method',
        'reference',
        'attachment_path',
        'status',
        'created_by',
        'converted_usd',
        'conversion_rate',
        'pkr_gross',
        'tax_applicable',
        'tax_type',
        'tax_value',
        'tax_amount',
        'pkr_net',
        'deposit_account_id',
        'conversion_date',
        'converted_by',
        'converted_at',
        'conversion_notes',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'payment_date' => 'date',
            'payment_method' => \App\Enums\PaymentMethod::class,
            'total_due' => 'decimal:2',
            'amount_received' => 'decimal:2',
            'advance_applied' => 'decimal:2',
            'status' => CompanyPaymentStatus::class,
            'converted_usd' => 'decimal:2',
            'conversion_rate' => 'decimal:4',
            'pkr_gross' => 'decimal:2',
            'tax_applicable' => 'boolean',
            'tax_type' => TaxType::class,
            'tax_value' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'pkr_net' => 'decimal:2',
            'conversion_date' => 'date',
            'converted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsToMany<Load, $this> */
    public function loads(): BelongsToMany
    {
        return $this->belongsToMany(Load::class, 'company_payment_loads')
            ->withPivot('applied_amount');
    }

    /** @return BelongsTo<Account, $this> */
    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deposit_account_id');
    }

    /** @return BelongsTo<User, $this> */
    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * What the company still owes on this receipt. Carried forward into the
     * Company Commission report rather than written off (Section 5.2).
     */
    public function shortfall(): float
    {
        return round(max(0, (float) $this->total_due - (float) $this->amount_received), 2);
    }

    public function isConverted(): bool
    {
        return $this->converted_at !== null;
    }

    /**
     * Once the USD is converted into PKR it is in the account ledger and editing
     * the receipt would desync the two, so it is frozen (Section 6.3).
     */
    public function isLocked(): bool
    {
        return $this->isConverted();
    }
}
