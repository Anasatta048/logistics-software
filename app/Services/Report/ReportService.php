<?php

namespace App\Services\Report;

use App\Enums\CompanyPaymentStatus;
use App\Enums\LoadStatus;
use App\Models\Invoice;
use App\Models\User;
use App\Repositories\Report\ReportRepository;
use App\Support\Timezones;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use stdClass;

/**
 * Orchestration over ReportRepository: role scoping, derived columns
 * (balances, rates, percentages) and the totals row each report shows. This is
 * what ReportController calls; it never touches the Repository directly.
 *
 * Money is always USD here unless a column says PKR.
 */
final class ReportService
{
    public function __construct(private readonly ReportRepository $repository)
    {
    }

    /* ───────────────────────────── Loads ───────────────────────────── */

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection<int, \App\Models\Load>, totals: array<string, float|int>}
     */
    public function loads(array $filters, User $viewer): array
    {
        $rows = $this->repository->loadRegister($this->scoped($filters, $viewer));
        $live = $rows->reject(fn ($load): bool => $load->status === LoadStatus::Cancelled);

        $miles = (float) $live->sum('total_miles');
        $rate = (float) $live->sum('rate');

        return [
            'rows' => $rows,
            'totals' => [
                'count' => $rows->count(),
                'cancelled' => $rows->count() - $live->count(),
                'miles' => $miles,
                'rate' => $rate,
                'rate_per_mile' => $miles > 0 ? round($rate / $miles, 2) : 0.0,
                'commission' => (float) $live->sum('company_commission_amount'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: BaseCollection<int, stdClass>, totals: array<string, float|int>}
     */
    public function drivers(array $filters, User $viewer): array
    {
        $rows = $this->repository->driverEarnings($this->scoped($filters, $viewer))
            ->map(function (stdClass $row): stdClass {
                $row->rate_per_mile = (float) $row->miles_total > 0 ? round((float) $row->rate_total / (float) $row->miles_total, 2) : 0.0;

                return $row;
            });

        $miles = (float) $rows->sum('miles_total');
        $rate = (float) $rows->sum('rate_total');

        return [
            'rows' => $rows,
            'totals' => [
                'loads' => (int) $rows->sum('loads_count'),
                'completed' => (int) $rows->sum('completed_count'),
                'cancelled' => (int) $rows->sum('cancelled_count'),
                'miles' => $miles,
                'rate' => $rate,
                'rate_per_mile' => $miles > 0 ? round($rate / $miles, 2) : 0.0,
                'commission' => (float) $rows->sum('commission_total'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: BaseCollection<int, stdClass>, totals: array<string, float|int>}
     */
    public function brokers(array $filters, User $viewer): array
    {
        $rows = $this->repository->brokerPerformance($this->scoped($filters, $viewer))
            ->map(function (stdClass $row): stdClass {
                $row->cancellation_rate = $row->loads_count > 0 ? round(($row->cancelled_count / $row->loads_count) * 100, 1) : 0.0;

                return $row;
            });

        return [
            'rows' => $rows,
            'totals' => [
                'loads' => (int) $rows->sum('loads_count'),
                'cancelled' => (int) $rows->sum('cancelled_count'),
                'revenue' => (float) $rows->sum('revenue_total'),
            ],
        ];
    }

    /** Age bands for money a broker still owes, in days since delivery. */
    public const AGING_BANDS = ['0-15' => [0, 15], '16-30' => [16, 30], '31-45' => [31, 45], '46-60' => [46, 60], '60+' => [61, PHP_INT_MAX]];

    /** A broker that pays later than this many days after delivery counts as "slow". */
    public const SLOW_PAY_DAYS = 30;

    /**
     * Per broker: what is still unpaid and how old it is, and how long the paid
     * loads took to pay. Age counts from delivery, in whole days.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: BaseCollection<int, stdClass>, open: BaseCollection<int, stdClass>, totals: array<string, float|int>}
     */
    public function brokerAging(array $filters, User $viewer): array
    {
        $from = ($filters['from'] ?? null) ? \Illuminate\Support\Carbon::parse($filters['from'], 'UTC')->startOfDay() : null;
        $to = ($filters['to'] ?? null) ? \Illuminate\Support\Carbon::parse($filters['to'], 'UTC')->endOfDay() : null;
        $now = now();

        $loads = $this->repository->brokerPayables($this->scoped($filters, $viewer))
            ->map(function (stdClass $load) use ($now): stdClass {
                $delivered = \Illuminate\Support\Carbon::parse($load->delivered_at ?? $load->dropoff_at, 'UTC');
                $paid = $load->paid_at ? \Illuminate\Support\Carbon::parse($load->paid_at, 'UTC') : null;

                $load->delivered = $delivered;
                $load->age_days = (int) $delivered->diffInDays($paid ?? $now);
                $load->is_paid = $load->status === LoadStatus::Paid->value || $load->status === LoadStatus::Paid;

                return $load;
            })
            ->filter(fn (stdClass $load): bool => (! $from || $load->delivered >= $from) && (! $to || $load->delivered <= $to));

        $band = fn (int $days): string => collect(self::AGING_BANDS)->search(fn (array $range): bool => $days >= $range[0] && $days <= $range[1]);

        $rows = $loads->groupBy('broker_id')->map(function (BaseCollection $group) use ($band): stdClass {
            $open = $group->reject->is_paid;
            $paid = $group->filter->is_paid;
            $row = (object) [
                'broker_id' => $group->first()->broker_id,
                'company_name' => $group->first()->company_name,
                'open_count' => $open->count(),
                'open_total' => round((float) $open->sum('rate'), 2),
                'oldest_days' => (int) ($open->max('age_days') ?? 0),
                'paid_count' => $paid->count(),
                'paid_total' => round((float) $paid->sum('rate'), 2),
                'avg_days_to_pay' => $paid->isEmpty() ? null : round((float) $paid->avg('age_days'), 1),
                'slow_pct' => $paid->isEmpty() ? null : round($paid->filter(fn ($l) => $l->age_days > self::SLOW_PAY_DAYS)->count() / $paid->count() * 100, 1),
            ];

            foreach (array_keys(self::AGING_BANDS) as $name) {
                $row->{'band_'.$name} = round((float) $open->filter(fn ($l) => $band($l->age_days) === $name)->sum('rate'), 2);
            }

            return $row;
        })->sortByDesc('open_total')->values();

        $open = $loads->reject->is_paid->sortByDesc('age_days')->values()
            ->map(function (stdClass $load) use ($band): stdClass {
                $load->band = $band($load->age_days);

                return $load;
            });

        return [
            'rows' => $rows,
            'open' => $open,
            'totals' => [
                'open_count' => (int) $rows->sum('open_count'),
                'open_total' => round((float) $rows->sum('open_total'), 2),
                'over_30' => round((float) $open->where('age_days', '>', self::SLOW_PAY_DAYS)->sum('rate'), 2),
                'avg_days_to_pay' => ($paidLoads = $loads->filter->is_paid)->isEmpty() ? 0.0 : round((float) $paidLoads->avg('age_days'), 1),
            ],
        ];
    }

    /**
     * Statement for one carrier company or driver: each finished load, the
     * company commission kept, and what the carrier takes home.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection<int, \App\Models\Load>, totals: array<string, float|int>}
     */
    public function settlement(array $filters, User $viewer): array
    {
        $rows = $this->repository->settlementLoads($this->scoped($filters, $viewer));

        $gross = round((float) $rows->sum('rate'), 2);
        $commission = round((float) $rows->sum('company_commission_amount'), 2);

        return [
            'rows' => $rows,
            'totals' => [
                'count' => $rows->count(),
                'miles' => (float) $rows->sum('total_miles'),
                'gross' => $gross,
                'commission' => $commission,
                'net' => round($gross - $commission, 2),
            ],
        ];
    }

    /* ────────────────────── Invoices & receipts ────────────────────── */

    /**
     * One row per invoice with the numbers a collector needs: billed, paid,
     * balance, and how late it is. A carried-forward invoice's balance lives on
     * the newer invoice, so it shows no balance of its own.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: BaseCollection<int, stdClass>, totals: array<string, float|int>}
     */
    public function invoices(array $filters): array
    {
        $today = Timezones::today();

        $rows = $this->repository->invoices($filters)->map(function (Invoice $invoice) use ($today): stdClass {
            $due = $invoice->due();
            $paid = round((float) ($invoice->paid_sum ?? 0), 2);
            $carried = $invoice->isCarried();
            $balance = $carried ? 0.0 : round(max(0, $due - $paid), 2);
            $overdue = $balance > 0 && $invoice->due_date->toDateString() < $today;

            return (object) [
                'invoice' => $invoice,
                'due' => $due,
                'paid' => $paid,
                'balance' => $balance,
                'carried' => $carried,
                'overdue' => $overdue,
                'days_overdue' => $overdue ? (int) $invoice->due_date->diffInDays($today) : 0,
                // pending | partially_settled | fully_settled | carried | overdue
                'state' => $carried ? 'carried' : ($overdue ? 'overdue' : CompanyPaymentStatus::forAmounts($due, $paid)->value),
                'status' => $carried ? 'Carried forward' : CompanyPaymentStatus::forAmounts($due, $paid)->label(),
            ];
        });

        if (($filters['status'] ?? '') !== '') {
            $rows = $rows->filter(fn (stdClass $row): bool => $row->state === $filters['status']
                || ($filters['status'] === 'open' && $row->balance > 0))->values();
        }

        return [
            'rows' => $rows,
            'totals' => [
                'count' => $rows->count(),
                'billed' => round((float) $rows->where('carried', false)->sum('due'), 2),
                'paid' => round((float) $rows->sum('paid'), 2),
                'balance' => round((float) $rows->sum('balance'), 2),
                'overdue' => round((float) $rows->where('overdue', true)->sum('balance'), 2),
            ],
        ];
    }

    /**
     * Per company: billed and received in the window, plus where it stands
     * today (open balance, overdue, credit held) and commission not yet billed.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: BaseCollection<int, stdClass>, totals: array<string, float|int>}
     */
    public function receivables(array $filters): array
    {
        $today = Timezones::today();

        $billed = $this->repository->invoices($filters)->groupBy('company_id');
        $open = $this->repository->openInvoices($filters)->groupBy('company_id');
        $received = $this->repository->receivedByCompany($filters);
        $unbilled = $this->repository->unbilledByCompany($filters);

        $rows = $this->repository->companies($filters)
            ->map(function ($company) use ($billed, $open, $received, $unbilled, $today): stdClass {
                $invoices = $billed->get($company->id, collect());
                $openInvoices = $open->get($company->id, collect());

                $balance = 0.0;
                $overdue = 0.0;
                $credit = 0.0;

                foreach ($openInvoices as $invoice) {
                    $paid = (float) ($invoice->paid_sum ?? 0);
                    $left = max(0, $invoice->due() - $paid);
                    $balance += $left;
                    $overdue += $invoice->due_date->toDateString() < $today ? $left : 0;
                    $credit += max(0, $paid - $invoice->due() - (float) $invoice->credit_used);
                }

                return (object) [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'mc_number' => $company->mc_number,
                    'invoices_count' => $invoices->count(),
                    'billed_total' => round((float) $invoices->reject(fn (Invoice $i): bool => $i->isCarried())->sum(fn (Invoice $i): float => $i->due()), 2),
                    'received_total' => round((float) ($received[$company->id] ?? 0), 2),
                    'balance_total' => round($balance, 2),
                    'overdue_total' => round($overdue, 2),
                    'credit_total' => round($credit, 2),
                    'unbilled_total' => round((float) ($unbilled[$company->id] ?? 0), 2),
                ];
            })
            // A company with nothing to show is noise, not a zero row.
            ->filter(fn (stdClass $row): bool => $row->invoices_count > 0 || $row->received_total > 0 || $row->balance_total > 0
                || $row->credit_total > 0 || $row->unbilled_total > 0)
            ->values();

        return [
            'rows' => $rows,
            'totals' => [
                'billed' => (float) $rows->sum('billed_total'),
                'received' => (float) $rows->sum('received_total'),
                'balance' => (float) $rows->sum('balance_total'),
                'overdue' => (float) $rows->sum('overdue_total'),
                'credit' => (float) $rows->sum('credit_total'),
                'unbilled' => (float) $rows->sum('unbilled_total'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection<int, \App\Models\CompanyPayment>, totals: array<string, float|int>}
     */
    public function receipts(array $filters): array
    {
        $rows = $this->repository->receipts($filters);

        return [
            'rows' => $rows,
            'totals' => [
                'count' => $rows->count(),
                'received' => (float) $rows->sum('amount_received'),
                'shortfall' => round((float) $rows->sum(fn ($payment): float => $payment->shortfall()), 2),
                'converted' => $rows->whereNotNull('converted_at')->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection<int, \App\Models\CompanyPayment>, totals: array<string, float|int>}
     */
    public function conversions(array $filters): array
    {
        $rows = $this->repository->conversions($filters);

        return [
            'rows' => $rows,
            'totals' => [
                'usd' => (float) $rows->sum('converted_usd'),
                'pkr_gross' => (float) $rows->sum('pkr_gross'),
                'tax' => (float) $rows->sum('tax_amount'),
                'pkr_net' => (float) $rows->sum('pkr_net'),
                'average_rate' => (float) $rows->sum('converted_usd') > 0
                    ? round((float) $rows->sum('pkr_gross') / (float) $rows->sum('converted_usd'), 4)
                    : 0.0,
            ],
        ];
    }

    /* ──────────────────────── Accounts & expenses ──────────────────────── */

    /**
     * Every category (so an empty one still shows as zero), with its share of the total.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: array<string, array{label: string, total: float, count: int, share: float}>, total: float, count: int}
     */
    public function expenses(array $filters, User $viewer): array
    {
        $scoped = $this->scoped($filters, $viewer);
        $found = $this->repository->expensesByCategory($scoped)->keyBy(fn (object $row): int => (int) $row->expense_category_id);

        $total = (float) $found->sum('total');
        $count = (int) $found->sum('expenses_count');
        $names = \App\Models\ExpenseCategory::query()->pluck('name', 'id');

        $rows = [];
        // Every category (an empty one shows as zero), then anything uncategorised.
        foreach ($names->keys()->concat($found->has(0) ? [0] : []) as $id) {
            $row = $found->get($id);
            $amount = (float) ($row->total ?? 0);

            $rows[$id] = [
                'label' => $id === 0 ? 'Uncategorised' : $names[$id],
                'total' => $amount,
                'count' => (int) ($row->expenses_count ?? 0),
                'share' => $total > 0 ? round($amount / $total * 100, 1) : 0.0,
            ];
        }

        $shape = fn (\Illuminate\Support\Collection $items): array => $items->map(fn (object $r): array => [
            'label' => (string) $r->label,
            'total' => (float) $r->total,
            'count' => (int) $r->expenses_count,
            'share' => $total > 0 ? round((float) $r->total / $total * 100, 1) : 0.0,
        ])->values()->all();

        $months = $shape($this->repository->expensesBy('month', $scoped));
        $monthsWithData = array_filter($months, fn (array $m): bool => $m['total'] > 0);

        return [
            'rows' => $rows,
            'total' => $total,
            'count' => $count,
            'average' => $count > 0 ? round($total / $count, 2) : 0.0,
            'monthlyAverage' => $monthsWithData !== [] ? round($total / count($monthsWithData), 2) : 0.0,
            'months' => $months,
            'accounts' => $shape($this->repository->expensesBy('account', $scoped)),
            'methods' => $shape($this->repository->expensesBy('method', $scoped)),
            'payees' => $shape($this->repository->expensesBy('payee', $scoped)),
        ];
    }

    /**
     * Not scoped by dispatcher: an account statement is an accounting
     * document, gated by `reports.view` + `accounts.view`, not by "own records".
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, \App\Models\AccountTransaction>
     */
    public function accountStatement(array $filters): Collection
    {
        return $this->repository->accountStatement($filters);
    }

    /* ─────────────────────── Dispatcher commissions ─────────────────────── */

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: BaseCollection<int, stdClass>, totals: array<string, float|int>}
     */
    public function dispatcherCommissions(array $filters, User $viewer): array
    {
        $rows = $this->repository->dispatcherCommissions($this->scoped($filters, $viewer));

        return [
            'rows' => $rows,
            'totals' => [
                'loads' => (int) $rows->sum('loads_count'),
                'margin' => (float) $rows->sum('margin_total'),
                'earned' => (float) $rows->sum('earned_total'),
                'paid' => (float) $rows->sum('paid_total'),
                'payable' => (float) $rows->sum('payable_total'),
                'reversed' => (float) $rows->sum('reversed_total'),
                'payouts_usd' => (float) $rows->sum('payouts_usd'),
                'payouts_pkr' => (float) $rows->sum('payouts_pkr'),
            ],
        ];
    }

    /* ───────────────────────── Profit & loss ───────────────────────── */

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function profitAndLoss(array $filters, User $viewer): array
    {
        $totals = $this->repository->profitAndLoss($this->scoped($filters, $viewer));

        $totals['margin_percentage'] = $totals['converted_pkr'] > 0
            ? round($totals['net_profit'] / $totals['converted_pkr'] * 100, 2)
            : 0.0;

        return $totals;
    }

    /**
     * Dispatchers can only ever request reports scoped to themselves — the
     * `user_id` filter is forced server-side for non-admin roles, never trusted
     * from the request.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function scoped(array $filters, User $viewer): array
    {
        if ($viewer->isScopedToOwnRecords()) {
            $filters['user_id'] = $viewer->id;
        }

        return $filters;
    }
}
