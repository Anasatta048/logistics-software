<?php

namespace App\Models;

use App\Enums\CompanyPaymentStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One driver's invoice to their company: the commission on the chosen loads.
 *
 * @property-read Company $company
 * @property-read Driver|null $driver
 * @property-read User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InvoiceLine> $lines
 */
class Invoice extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'invoice_number', 'company_id', 'driver_id', 'driver_name',
        'invoice_date', 'due_date', 'period_from', 'period_to',
        'dispatcher_name', 'dispatcher_phone',
        'gross_total', 'total', 'previous_balance', 'credit_applied', 'credit_used', 'carried_to_id', 'carryover',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'period_from' => 'date',
            'period_to' => 'date',
            'gross_total' => 'decimal:2',
            'total' => 'decimal:2',
            'previous_balance' => 'decimal:2',
            'credit_applied' => 'decimal:2',
            'credit_used' => 'decimal:2',
            'carryover' => 'array',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    /** @return BelongsTo<Driver, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<InvoiceLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('load_date')->orderBy('id');
    }

    /** @return HasMany<CompanyPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(CompanyPayment::class)->orderBy('payment_date')->orderBy('id');
    }

    /** @return BelongsTo<Invoice, $this> */
    public function carriedTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'carried_to_id');
    }

    /** The number as the company writes it: optional prefix + number (INV-1001). */
    public function label(): string
    {
        return config('invoice.prefix', '').$this->invoice_number;
    }

    public function amountPaid(): float
    {
        return round((float) $this->payments()->sum('amount_received'), 2);
    }

    /** What the company is asked to pay: the loads' commission, plus/minus any carry-over. */
    public function due(): float
    {
        return round((float) $this->total + (float) $this->previous_balance - (float) $this->credit_applied, 2);
    }

    public function balance(): float
    {
        return round(max(0, $this->due() - $this->amountPaid()), 2);
    }

    /** Paid beyond what was due and not yet used on a later invoice. */
    public function creditAvailable(): float
    {
        return round(max(0, $this->amountPaid() - $this->due() - (float) $this->credit_used), 2);
    }

    /** Its unpaid balance was moved onto a newer invoice, so it no longer counts as open. */
    public function isCarried(): bool
    {
        return $this->carried_to_id !== null;
    }

    /** Pending until money arrives, then partially or fully settled. */
    public function status(): CompanyPaymentStatus
    {
        return CompanyPaymentStatus::forAmounts($this->due(), $this->amountPaid());
    }

    public function fileName(): string
    {
        // e.g. "JAMES GOODMAN INVOICE 1329.pdf"
        $driver = trim(preg_replace('/[^A-Za-z0-9 \-]+/', '', (string) $this->driver_name));

        return trim(strtoupper($driver).' INVOICE '.$this->label()).'.pdf';
    }
}
