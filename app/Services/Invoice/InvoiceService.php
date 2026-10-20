<?php

namespace App\Services\Invoice;

use App\Enums\LoadStatus;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Load;
use App\Models\User;
use App\Support\Timezones;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Driver invoices. The amount billed is the company's commission on each load
 * (rate × the percentage frozen on the load), never the full load rate; the
 * rate total is carried along only as the "total weekly gross" line.
 */
final class InvoiceService
{
    /**
     * Loads of this driver that can still be invoiced in the date range: run for
     * the driver's company, delivered or paid, and not on another invoice.
     *
     * @return Collection<int, Load>
     */
    public function eligibleLoads(Driver $driver, string $from, string $to): Collection
    {
        return $this->eligibleQuery($driver)
            ->where('pickup_at', '>=', Timezones::parseUs($from.' 00:00:00'))
            ->where('pickup_at', '<=', Timezones::parseUs($to.' 23:59:59'))
            ->orderBy('pickup_at')
            ->get();
    }

    /**
     * What a company carries from earlier invoices, offered (never forced) on the
     * next one: unpaid balances to add, and credit from overpayments to deduct.
     *
     * @return array{pending: float, credit: float, pending_invoices: list<array<string, mixed>>, credit_invoices: list<array<string, mixed>>}
     */
    public function carryOver(Company $company): array
    {
        $pending = [];
        $credit = [];

        foreach (Invoice::query()->where('company_id', $company->id)->whereNull('carried_to_id')->orderBy('id')->get() as $invoice) {
            if ($invoice->balance() > 0) {
                $pending[] = ['id' => $invoice->id, 'number' => $invoice->invoice_number, 'amount' => $invoice->balance()];
            }

            if ($invoice->creditAvailable() > 0) {
                $credit[] = ['id' => $invoice->id, 'number' => $invoice->invoice_number, 'amount' => $invoice->creditAvailable()];
            }
        }

        return [
            'pending' => round(array_sum(array_column($pending, 'amount')), 2),
            'credit' => round(array_sum(array_column($credit, 'amount')), 2),
            'pending_invoices' => $pending,
            'credit_invoices' => $credit,
        ];
    }

    /**
     * @param  array{driver_id: int, invoice_date: string, due_date: string, period_from: string, period_to: string, load_ids: list<int>, notes?: ?string, include_previous?: bool, use_credit?: bool}  $data
     */
    public function create(array $data, User $actor): Invoice
    {
        return DB::transaction(function () use ($data, $actor): Invoice {
            $driver = Driver::query()->with('company')->findOrFail($data['driver_id']);

            $loads = $this->eligibleQuery($driver)
                ->whereIn('id', $data['load_ids'])
                ->with('dispatcher:id,name,phone')
                ->orderBy('pickup_at')
                ->lockForUpdate()
                ->get();

            // Something was invoiced, cancelled or reassigned since the form loaded.
            if ($loads->count() !== count(array_unique($data['load_ids']))) {
                throw ValidationException::withMessages([
                    'load_ids' => 'Some of the selected loads can no longer be invoiced. Reload the list and pick again.',
                ]);
            }

            $dispatcher = $loads->pluck('dispatcher')->filter()->first();

            $fees = round($loads->sum(fn (Load $load): float => (float) $load->company_commission_amount), 2);
            $carry = $this->carryOver($driver->company);
            $previous = ! empty($data['include_previous']) ? $carry['pending'] : 0.0;
            $credit = ! empty($data['use_credit']) ? min($carry['credit'], round($fees + $previous, 2)) : 0.0;

            $invoice = Invoice::create([
                'invoice_number' => $this->nextNumber(),
                'company_id' => $driver->company_id,
                'driver_id' => $driver->id,
                'driver_name' => $driver->name,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'period_from' => $data['period_from'],
                'period_to' => $data['period_to'],
                'dispatcher_name' => $dispatcher?->name,
                'dispatcher_phone' => $dispatcher?->phone,
                'gross_total' => round($loads->sum(fn (Load $load): float => (float) $load->rate), 2),
                'total' => $fees,
                'previous_balance' => $previous,
                'credit_applied' => $credit,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->applyCarryOver($invoice, $carry, $previous > 0, $credit);

            foreach ($loads as $load) {
                $invoice->lines()->create([
                    'load_id' => $load->id,
                    'load_date' => Timezones::toUs($load->pickup_at)->toDateString(),
                    'description' => $load->origin_address.' TO '.$load->destination_address,
                    'miles' => $load->total_miles,
                    'rate' => $load->rate,
                    'percentage' => $load->company_commission_percentage,
                    'fee' => $load->company_commission_amount,
                ]);
            }

            return $invoice->load(['company', 'driver', 'lines']);
        });
    }

    public function delete(Invoice $invoice): void
    {
        if ($invoice->payments()->exists()) {
            throw ValidationException::withMessages([
                'invoice' => 'This invoice already has payments recorded against it and cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($invoice): void {
            // Give back the credit this invoice used; its carried balances are
            // released by the carried_to_id foreign key (null on delete).
            foreach ($invoice->carryover['credits'] ?? [] as $sourceId => $amount) {
                Invoice::query()->whereKey($sourceId)->decrement('credit_used', (float) $amount);
            }

            // Lines go with it (cascade), which frees their loads for a new invoice.
            $invoice->delete();
        });
    }

    /**
     * Mark the earlier invoices whose balance or credit moved onto this one, so
     * the same money is never offered twice.
     *
     * @param  array<string, mixed>  $carry
     */
    private function applyCarryOver(Invoice $invoice, array $carry, bool $takePending, float $credit): void
    {
        $record = ['pending' => [], 'credits' => []];

        if ($takePending) {
            $ids = array_column($carry['pending_invoices'], 'id');
            Invoice::query()->whereIn('id', $ids)->update(['carried_to_id' => $invoice->id]);
            $record['pending'] = $ids;
        }

        $left = $credit;

        foreach ($carry['credit_invoices'] as $source) {
            if ($left <= 0) {
                break;
            }

            $use = round(min($left, (float) $source['amount']), 2);
            Invoice::query()->whereKey($source['id'])->increment('credit_used', $use);
            $record['credits'][$source['id']] = $use;
            $left = round($left - $use, 2);
        }

        if ($record['pending'] !== [] || $record['credits'] !== []) {
            $invoice->update(['carryover' => $record]);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Invoice>
     */
    public function paginate(array $filters = [], int $perPage = 500): LengthAwarePaginator
    {
        $term = trim((string) ($filters['q'] ?? ''));

        return Invoice::query()
            ->with(['company:id,name,mc_number'])
            ->withCount('lines')
            ->withSum('payments as paid_sum', 'amount_received')
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $where) => $where
                ->where('invoice_number', 'like', "%{$term}%")
                ->orWhere('driver_name', 'like', "%{$term}%")
                ->orWhereHas('company', fn (Builder $company) => $company
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('mc_number', 'like', "%{$term}%"))))
            ->when(($filters['from'] ?? '') !== '', fn (Builder $query) => $query->where('invoice_date', '>=', (string) $filters['from']))
            ->when(($filters['to'] ?? '') !== '', fn (Builder $query) => $query->where('invoice_date', '<=', (string) $filters['to']))
            ->when(($filters['status'] ?? '') !== '', function (Builder $query) use ($filters) {
                $paid = '(SELECT COALESCE(SUM(amount_received), 0) FROM company_payments WHERE company_payments.invoice_id = invoices.id AND company_payments.deleted_at IS NULL)';

                $due = 'invoices.total + invoices.previous_balance - invoices.credit_applied';

                match ($filters['status']) {
                    'pending' => $query->whereRaw("{$paid} <= 0"),
                    'partially_settled' => $query->whereRaw("{$paid} > 0 AND {$paid} < ({$due})"),
                    'fully_settled' => $query->whereRaw("{$paid} >= ({$due})"),
                    default => null,
                };
            })
            ->latest('invoice_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function pdf(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->loadMissing(['company', 'driver', 'lines']);

        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->setPaper('letter');
    }

    /** @return Builder<Load> */
    private function eligibleQuery(Driver $driver): Builder
    {
        return Load::query()
            ->where('driver_id', $driver->id)
            ->where('company_id', $driver->company_id)
            ->whereIn('status', [LoadStatus::Delivered, LoadStatus::Paid])
            ->whereDoesntHave('invoiceLine');
    }

    private function nextNumber(): int
    {
        $last = (int) Invoice::query()->lockForUpdate()->max('invoice_number');

        return max($last + 1, (int) config('invoice.start_number'));
    }
}
