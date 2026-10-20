<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A load on an invoice, frozen as it was billed: later edits to the load do not
 * rewrite an invoice that has already gone out.
 */
class InvoiceLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['invoice_id', 'load_id', 'load_date', 'description', 'miles', 'rate', 'percentage', 'fee'];

    protected function casts(): array
    {
        return [
            'load_date' => 'date',
            'miles' => 'decimal:1',
            'rate' => 'decimal:2',
            'percentage' => 'decimal:2',
            'fee' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<Load, $this> */
    public function loadRecord(): BelongsTo
    {
        return $this->belongsTo(Load::class)->withTrashed();
    }

    /** Rate per mile of the load, for the RPM column. */
    public function ratePerMile(): ?float
    {
        return (float) $this->miles > 0 ? round((float) $this->rate / (float) $this->miles, 2) : null;
    }
}
