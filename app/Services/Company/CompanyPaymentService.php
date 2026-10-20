<?php

namespace App\Services\Company;

use App\Enums\CompanyPaymentStatus;
use App\Enums\LedgerEntryType;
use App\Enums\TaxType;
use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyPayment;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Load;
use App\Models\User;
use App\Services\Account\AccountService;
use App\Services\Ledger\LedgerService;
use App\Services\Support\AttachmentOptimizer;
use App\Support\Timezones;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * FLOW_UPDATE_PLAN Section 5: the weekly USD receipt.
 *
 * What a company still owes is a running balance, not a flag: the commission on
 * its Paid loads, less everything received, less any advance it already paid
 * ahead. A receipt walks that balance oldest load first, and whatever is left
 * over becomes advance so the next receipt eats it instead of the company being
 * double-credited.
 *
 * Each receipt is posted to the company's ledger (LedgerService) and can be
 * converted to PKR in the same save or later with convert(); the PKR lands in an
 * account through AccountService.
 */
final class CompanyPaymentService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly AccountService $accounts,
    ) {
    }

    /**
     * Records money received from a company. Any amount is fine:
     *
     *  - less than what it owes  → a partial payment; the rest stays outstanding,
     *  - exactly what it owes    → settles everything open,
     *  - more than what it owes  → the excess is an advance, held on the ledger
     *    and used up automatically by the company's next commissions.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): CompanyPayment
    {
        return DB::transaction(function () use ($data, $actor): CompanyPayment {
            $company = Company::query()->whereKey((int) $data['company_id'])->lockForUpdate()->firstOrFail();

            $payment = $this->record($company, null, $data, $actor);

            // Received in USD and converted the same day: one form, one save.
            if (filter_var($data['convert'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $payment = $this->convert($payment, [...$data, 'conversion_date' => $data['conversion_date'] ?? $data['payment_date']], $actor);
            }

            return $payment;
        });
    }

    /**
     * A receipt is corrected by reversing what it posted and posting it again,
     * never by rewriting the ledger — both steps stay visible on the statement.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CompanyPayment $payment, array $data, User $actor): CompanyPayment
    {
        // Once the USD is in the PKR ledger the receipt is history: corrections
        // are a new JV or conversion, not an edit (Section 6.3).
        if ($payment->isLocked()) {
            throw ValidationException::withMessages([
                'company_payment_id' => 'This payment has already been converted to PKR and can no longer be edited.',
            ]);
        }

        return DB::transaction(function () use ($payment, $data, $actor): CompanyPayment {
            $company = Company::query()->whereKey((int) $payment->company_id)->lockForUpdate()->firstOrFail();

            $this->ledger->post(
                $company,
                LedgerEntryType::PaymentReversed,
                (float) $payment->amount_received,
                sprintf('Payment #%d corrected — original $%s reversed', $payment->id, number_format((float) $payment->amount_received, 2)),
                source: $payment,
                actor: $actor,
                occurredOn: Timezones::today(),
            );
            $payment->loads()->detach();

            return $this->record($company, $payment, $data, $actor);
        });
    }


    /**
     * The rate the last conversion used, to pre-fill the next one. Null until
     * the first conversion exists.
     */
    public function lastRate(): ?float
    {
        $rate = CompanyPayment::query()
            ->whereNotNull('converted_at')
            ->orderByDesc('conversion_date')
            ->orderByDesc('id')
            ->value('conversion_rate');

        return $rate === null ? null : (float) $rate;
    }

    /**
     * Turns the USD received into PKR and deposits the net. Done in the payment
     * form or later from the payment page — either way it is one write on this
     * record, and the account ledger line goes through AccountService (the one
     * choke point for balances) in the same transaction, so the payment can never
     * say "converted" without the money being in the account.
     *
     * @param  array<string, mixed>  $data  conversion_rate, deposit_account_id, conversion_date,
     *                                      usd_amount?, tax_applicable?, tax_type?, tax_value?, conversion_notes?
     */
    public function convert(CompanyPayment $payment, array $data, User $actor): CompanyPayment
    {
        return DB::transaction(function () use ($payment, $data, $actor): CompanyPayment {
            // Re-read under a lock: two people converting the same receipt must
            // not both deposit it.
            $payment = CompanyPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->isConverted()) {
                throw ValidationException::withMessages([
                    'conversion_rate' => 'This payment has already been converted to PKR.',
                ]);
            }

            $received = (float) $payment->amount_received;
            $usd = round((float) ($data['usd_amount'] ?? $received), 2);

            // Only money that actually arrived may be converted, never more.
            if ($received <= 0) {
                throw ValidationException::withMessages([
                    'conversion_rate' => 'Nothing was received on this payment, so there is nothing to convert.',
                ]);
            }

            if ($usd <= 0 || $usd > $received + 0.001) {
                throw ValidationException::withMessages([
                    'usd_amount' => 'The amount to convert must be more than zero and no more than was received.',
                ]);
            }

            $rate = round((float) $data['conversion_rate'], 4);
            $gross = round($usd * $rate, 2);
            $taxable = (bool) ($data['tax_applicable'] ?? false);
            $tax = $this->taxAmount($gross, $taxable, $data['tax_type'] ?? null, (float) ($data['tax_value'] ?? 0));
            $net = round($gross - $tax, 2);
            // The deposit account is optional: without one the PKR is still recorded
            // on the payment, it just isn't posted to any account's balance.
            $account = filled($data['deposit_account_id'] ?? null)
                ? Account::query()->findOrFail((int) $data['deposit_account_id'])
                : null;

            $payment->update([
                'converted_usd' => $usd,
                'conversion_rate' => $rate,
                'pkr_gross' => $gross,
                'tax_applicable' => $taxable,
                'tax_type' => $taxable ? $data['tax_type'] : null,
                'tax_value' => $taxable ? $data['tax_value'] : null,
                'tax_amount' => $tax,
                'pkr_net' => $net,
                'deposit_account_id' => $account?->id,
                'conversion_date' => $data['conversion_date'] ?? $payment->payment_date,
                'converted_by' => $actor->id,
                'converted_at' => now(),
                'conversion_notes' => $data['conversion_notes'] ?? null,
            ]);

            // Debit, not credit: in this ledger an account is an asset, so money
            // arriving is a debit.
            if ($account !== null) {
                $this->accounts->recordTransaction(
                    $account,
                    TransactionType::Debit,
                    $net,
                    TransactionSource::CurrencyConversion,
                    $payment->id,
                    sprintf(
                        'USD %s @ %s from %s, week of %s',
                        number_format($usd, 2),
                        number_format($rate, 2),
                        $payment->company->name,
                        $payment->period_from->format('M j').' - '.$payment->period_to->format('M j, Y'),
                    ),
                    $actor,
                );
            }

            return $payment->refresh();
        });
    }

    /**
     * Percentage takes a share of the gross; fixed is already the amount. No
     * tax means net equals gross.
     */
    private function taxAmount(float $gross, bool $applicable, mixed $type, float $value): float
    {
        if (! $applicable || $value <= 0) {
            return 0.0;
        }

        return round($type === TaxType::Fixed->value ? $value : $gross * $value / 100, 2);
    }

    /**
     * Shared by create and update: match the amount to the company's open
     * commissions oldest first, save the receipt and post it to the ledger.
     *
     * @param  array<string, mixed>  $data
     */
    private function record(Company $company, ?CompanyPayment $payment, array $data, User $actor): CompanyPayment
    {
        $received = round((float) $data['amount_received'], 2);
        $position = $this->ledger->position($company);
        $allocations = $this->allocate($position['items'], $received);
        $due = $this->dueFor($data['invoice_id'] ?? $payment?->invoice_id, $payment, $position['outstanding']);

        $attributes = [
            'company_id' => $company->id,
            'period_from' => $data['period_from'],
            'period_to' => $data['period_to'],
            // What the company owed when this money arrived. Less received than
            // this is a partial payment; more is an advance.
            'total_due' => $due,
            'amount_received' => $received,
            'advance_applied' => 0,
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'] ?? null,
            'reference' => $data['reference'] ?? null,
            'status' => CompanyPaymentStatus::forAmounts($due, $received),
        ];

        if ($payment === null) {
            $payment = CompanyPayment::create([
                ...$attributes,
                'invoice_id' => $data['invoice_id'] ?? null,
                'attachment_path' => isset($data['attachment']) ? app(AttachmentOptimizer::class)->store($data['attachment'], 'company-payments') : null,
                'created_by' => $actor->id,
            ]);
        } else {
            $payment->update($attributes);
        }

        $this->attach($payment, $allocations);

        $applied = round(array_sum(array_column($allocations, 'amount')), 2);
        $advance = round(max(0, $received - max($due, $applied)), 2);

        $this->ledger->post(
            $company,
            LedgerEntryType::PaymentReceived,
            -$received,
            $this->describe($payment, $received, $due, $advance),
            source: $payment,
            actor: $actor,
            occurredOn: (string) Carbon::parse($data['payment_date'])->toDateString(),
        );

        return $payment->refresh();
    }

    /**
     * What this receipt is measured against. A payment for an invoice owes that
     * invoice's balance (before this payment); anything else owes the company's
     * ledger position. Using the ledger for an invoice payment showed "due $0 /
     * fully settled" for a partial payment whenever the loads had not accrued yet.
     */
    private function dueFor(?int $invoiceId, ?CompanyPayment $payment, float $ledgerOutstanding): float
    {
        $invoice = $invoiceId === null ? null : Invoice::query()->find($invoiceId);

        if ($invoice === null) {
            return $ledgerOutstanding;
        }

        // When editing, this receipt's own amount is already counted as paid.
        $paidByOthers = $invoice->amountPaid() - (float) ($payment?->amount_received ?? 0);

        return round(max(0, $invoice->due() - $paidByOthers), 2);
    }

    private function describe(CompanyPayment $payment, float $received, float $due, float $advance): string
    {
        $text = sprintf('Payment #%d received: $%s', $payment->id, number_format($received, 2));

        if ($payment->reference) {
            $text .= " ({$payment->reference})";
        }

        return match (true) {
            $advance > 0 => $text.sprintf(' — $%s advance held beyond the $%s owed', number_format($advance, 2), number_format($due, 2)),
            $received < $due => $text.sprintf(' — partial, $%s still owed', number_format($due - $received, 2)),
            default => $text.' — settled in full',
        };
    }

    /**
     * Hand an amount against the company's open items, oldest first. One receipt
     * can cover several loads, or part of one.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $items
     * @return list<array{load: Load, amount: float}>
     */
    private function allocate($items, float $available): array
    {
        $open = $items->filter(fn (object $item): bool => $item->load_id !== null && $item->remaining > 0.005);
        $loads = Load::query()->whereIn('id', $open->pluck('load_id'))->get()->keyBy('id');

        $allocations = [];
        $remaining = $available;

        foreach ($open as $item) {
            if ($remaining <= 0.005) {
                break;
            }

            $amount = round(min($item->remaining, $remaining), 2);
            $allocations[] = ['load' => $loads[$item->load_id], 'amount' => $amount];
            $remaining = round($remaining - $amount, 2);
        }

        return $allocations;
    }

    /**
     * What this company owes, has paid and holds in advance — straight from the
     * ledger, so every screen quotes the same number.
     *
     * @return array{invoiced: float, received: float, advance: float, outstanding: float}
     */
    public function balance(Company $company): array
    {
        $position = $this->ledger->position($company);

        $received = -(float) LedgerEntry::query()
            ->where('party_type', LedgerEntry::COMPANY)
            ->where('party_id', $company->id)
            ->whereIn('entry_type', [LedgerEntryType::PaymentReceived->value, LedgerEntryType::PaymentReversed->value])
            ->sum('amount');

        return [
            'invoiced' => $position['accrued'],
            'received' => round($received, 2),
            'advance' => $position['advance'],
            'outstanding' => $position['outstanding'],
        ];
    }

    /**
     * The loads a receipt would cover next: the company's open commissions in
     * the order they are settled, each with what is still owed on it.
     *
     * @return Collection<int, Load>
     */
    public function suggestableLoads(Company $company, ?string $from, ?string $to): Collection
    {
        $open = $this->ledger->position($company)['items']
            ->filter(fn (object $item): bool => $item->load_id !== null && $item->remaining > 0.005)
            ->keyBy('load_id');

        return Load::query()
            ->whereIn('id', $open->keys())
            ->when($from, fn ($query, $date) => $query->where('pickup_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($to, fn ($query, $date) => $query->where('pickup_at', '<=', Carbon::parse($date)->endOfDay()))
            ->get()
            ->sortBy(fn (Load $load): int => $open->keys()->search($load->id))
            ->map(function (Load $load) use ($open): Load {
                $load->outstanding_commission = $open[$load->id]->remaining;

                return $load;
            })
            ->values();
    }

    /**
     * The settlement a load's commission was already paid out through, used for
     * the warning on the cancellation screen. The latest wins, because a load
     * can legitimately sit in an adjustment line after that.
     */
    public function settledIn(Load $load): ?CompanyPayment
    {
        /** @var CompanyPayment|null $payment */
        $payment = $load->payments()
            ->where('company_payments.deleted_at', null)
            ->where('total_due', '>', 0)
            ->orderByDesc('company_payments.id')
            ->first();

        return $payment;
    }

    /**
     * A negative line for a load that was already paid out and then cancelled
     * (Section 5.4). Never edits the original receipt — the money really was
     * received, the reversal belongs to the next period.
     */
    public function createAdjustment(Load $load, User $actor): CompanyPayment
    {
        return DB::transaction(function () use ($load, $actor): CompanyPayment {
            $amount = -(float) $load->company_commission_amount;

            $payment = CompanyPayment::create([
                'company_id' => $load->company_id,
                'period_from' => now()->startOfWeek()->toDateString(),
                'period_to' => now()->endOfWeek()->toDateString(),
                'total_due' => $amount,
                'amount_received' => 0,
                'payment_date' => now()->toDateString(),
                'reference' => 'ADJ-'.str_pad((string) $load->id, 6, '0', STR_PAD_LEFT),
                'status' => CompanyPaymentStatus::Pending,
                'created_by' => $actor->id,
            ]);

            $payment->loads()->attach($load->id, ['applied_amount' => $amount]);

            return $payment->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, CompanyPayment>
     */
    public function paginate(array $filters = [], int $perPage = 500): LengthAwarePaginator
    {
        return $this->query($filters)
            ->latest('payment_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, CompanyPayment>
     */
    public function all(array $filters = []): Collection
    {
        return $this->query($filters)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * Payments still free to be converted or edited.
     *
     * @return Collection<int, CompanyPayment>
     */
    public function convertible(): Collection
    {
        return CompanyPayment::query()
            ->with(['company:id,name'])
            ->whereNull('deleted_at')
            ->whereNull('converted_at')
            ->where('amount_received', '>', 0)
            ->orderByDesc('payment_date')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Builder<CompanyPayment>
     */
    private function query(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $status = ($filters['status'] ?? '') !== '' ? CompanyPaymentStatus::tryFrom((string) $filters['status']) : null;

        // Relations eager-loaded whole — see convertible() for why a column
        // list here would hand the views nulls.
        return CompanyPayment::query()
            ->with(['company:id,name,mc_number'])
            ->when($filters['company_id'] ?? null, fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when(($filters['from'] ?? '') !== '', fn ($query) => $query->where('payment_date', '>=', (string) $filters['from']))
            ->when(($filters['to'] ?? '') !== '', fn ($query) => $query->where('payment_date', '<=', (string) $filters['to']));
    }

    /**
     * Record which loads this receipt covered and how much of each, which is
     * what `allocate()` reads next time to work out what is still owed.
     *
     * @param  list<array{load: Load, amount: float}>  $allocations
     */
    private function attach(CompanyPayment $payment, array $allocations): void
    {
        // sync() takes [id => pivot attributes], not a list of rows — passing
        // rows throws on every write.
        $rows = [];
        foreach ($allocations as $row) {
            $rows[$row['load']->id] = ['applied_amount' => $row['amount']];
        }

        $payment->loads()->sync($rows);
    }
}
