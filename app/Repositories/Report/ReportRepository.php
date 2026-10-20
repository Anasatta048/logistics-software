<?php

namespace App\Repositories\Report;

use App\Enums\CommissionStatus;
use App\Enums\CompanyPaymentStatus;
use App\Enums\LoadStatus;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Commission;
use App\Models\CommissionPayout;
use App\Models\Company;
use App\Models\CompanyPayment;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Load;
use App\Models\User;
use App\Support\Timezones;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * The one module allowed a Repository layer: report queries are complex, reused
 * across endpoints, and kept apart from the Service layer's scoping and
 * formatting. No HTTP concerns live here: every method takes a plain filter
 * array and returns a collection/array.
 *
 * Reports follow the business flow, in order:
 *   Loads → Invoices → Payment receipts → PKR conversion → Accounts & expenses
 *   → Dispatcher commissions → Profit & loss.
 *
 * Filter keys: from, to (Y-m-d, read as entry-zone days), user_id (dispatcher),
 * broker_id, driver_id, company_id, account_id, status. ReportService scopes
 * user_id down to the current dispatcher for non-privileged roles.
 */
final class ReportRepository
{
    /* ───────────────────────────── Loads ───────────────────────────── */

    /**
     * Every load in the window (by pickup), cancelled ones included with their
     * reason, so one register answers both "what ran" and "what fell through".
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Load>
     */
    public function loadRegister(array $filters): Collection
    {
        $status = ($filters['status'] ?? '') !== '' ? LoadStatus::tryFrom((string) $filters['status']) : null;

        return Load::query()
            ->with(['company:id,name', 'broker:id,company_name', 'driver:id,name,truck_number', 'dispatcher:id,name', 'cancelledBy:id,name'])
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->when($filters['company_id'] ?? null, fn (Builder $q, $v) => $q->where('company_id', $v))
            ->when($filters['broker_id'] ?? null, fn (Builder $q, $v) => $q->where('broker_id', $v))
            ->when($filters['driver_id'] ?? null, fn (Builder $q, $v) => $q->where('driver_id', $v))
            ->when($filters['user_id'] ?? null, fn (Builder $q, $v) => $q->where('dispatcher_id', $v))
            ->when($from = $this->start($filters['from'] ?? null), fn (Builder $q) => $q->where('pickup_at', '>=', $from))
            ->when($to = $this->end($filters['to'] ?? null), fn (Builder $q) => $q->where('pickup_at', '<=', $to))
            ->orderByDesc('pickup_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Per driver: what they hauled, what it paid, and the company's cut.
     * Cancelled loads are counted but never add to miles or money.
     *
     * @param  array<string, mixed>  $filters
     * @return BaseCollection<int, stdClass>
     */
    public function driverEarnings(array $filters): BaseCollection
    {
        $live = "loads.status != '".LoadStatus::Cancelled->value."'";
        $done = "loads.status IN ('".LoadStatus::Delivered->value."', '".LoadStatus::Paid->value."')";

        return Load::query()
            ->join('drivers', 'drivers.id', '=', 'loads.driver_id')
            ->leftJoin('companies', 'companies.id', '=', 'drivers.company_id')
            ->when($filters['driver_id'] ?? null, fn (Builder $q, $v) => $q->where('loads.driver_id', $v))
            ->when($filters['company_id'] ?? null, fn (Builder $q, $v) => $q->where('drivers.company_id', $v))
            ->when($filters['user_id'] ?? null, fn (Builder $q, $v) => $q->where('loads.dispatcher_id', $v))
            ->when($from = $this->start($filters['from'] ?? null), fn (Builder $q) => $q->where('loads.pickup_at', '>=', $from))
            ->when($to = $this->end($filters['to'] ?? null), fn (Builder $q) => $q->where('loads.pickup_at', '<=', $to))
            ->groupBy('loads.driver_id', 'drivers.name', 'drivers.truck_number', 'companies.name')
            ->selectRaw('loads.driver_id, drivers.name as driver_name, drivers.truck_number, companies.name as company_name')
            ->selectRaw("SUM(CASE WHEN {$live} THEN 1 ELSE 0 END) as loads_count")
            ->selectRaw("SUM(CASE WHEN {$done} THEN 1 ELSE 0 END) as completed_count")
            ->selectRaw("SUM(CASE WHEN loads.status = '".LoadStatus::Cancelled->value."' THEN 1 ELSE 0 END) as cancelled_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN {$live} THEN loads.total_miles END), 0) as miles_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN {$live} THEN loads.rate END), 0) as rate_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN {$live} THEN loads.company_commission_amount END), 0) as commission_total")
            ->selectRaw('MAX(loads.pickup_at) as last_pickup')
            ->orderBy('drivers.name')
            ->toBase()
            ->get();
    }

    /**
     * Per broker: volume, what the paid loads brought in, and how often loads
     * with them fall through.
     *
     * @param  array<string, mixed>  $filters
     * @return BaseCollection<int, stdClass>
     */
    public function brokerPerformance(array $filters): BaseCollection
    {
        return Load::query()
            ->join('brokers', 'brokers.id', '=', 'loads.broker_id')
            ->when($filters['broker_id'] ?? null, fn (Builder $q, $v) => $q->where('loads.broker_id', $v))
            ->when($from = $this->start($filters['from'] ?? null), fn (Builder $q) => $q->where('loads.pickup_at', '>=', $from))
            ->when($to = $this->end($filters['to'] ?? null), fn (Builder $q) => $q->where('loads.pickup_at', '<=', $to))
            ->groupBy('loads.broker_id', 'brokers.company_name')
            ->selectRaw('loads.broker_id, brokers.company_name')
            ->selectRaw('COUNT(*) as loads_count')
            ->selectRaw("COALESCE(SUM(CASE WHEN loads.status != 'cancelled' THEN loads.total_miles END), 0) as miles_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN loads.status = 'paid' THEN loads.rate END), 0) as revenue_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN loads.status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_count")
            ->orderBy('brokers.company_name')
            ->toBase()
            ->get();
    }

    /* ────────────────────── Invoices & receipts ────────────────────── */

    /**
     * Invoices in the window with what has been paid on each, newest first.
     * Balance, status and overdue-ness are derived in ReportService.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Invoice>
     */
    public function invoices(array $filters): Collection
    {
        return Invoice::query()
            ->with(['company:id,name', 'driver:id,name'])
            ->withSum('payments as paid_sum', 'amount_received')
            ->when($filters['company_id'] ?? null, fn (Builder $q, $v) => $q->where('company_id', $v))
            ->when($filters['driver_id'] ?? null, fn (Builder $q, $v) => $q->where('driver_id', $v))
            ->when($from = $this->day($filters['from'] ?? null), fn (Builder $q) => $q->where('invoice_date', '>=', $from))
            ->when($to = $this->day($filters['to'] ?? null), fn (Builder $q) => $q->where('invoice_date', '<=', $to))
            ->orderByDesc('invoice_date')
            ->orderByDesc('invoice_number')
            ->get();
    }

    /**
     * Every open (not carried-forward) invoice's money, whatever the window:
     * outstanding balances and held credit are "as of today".
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Invoice>
     */
    public function openInvoices(array $filters): Collection
    {
        return Invoice::query()
            ->withSum('payments as paid_sum', 'amount_received')
            ->whereNull('carried_to_id')
            ->when($filters['company_id'] ?? null, fn (Builder $q, $v) => $q->where('company_id', $v))
            ->get();
    }

    /**
     * Received money per company inside the window.
     *
     * @param  array<string, mixed>  $filters
     * @return BaseCollection<int|string, float>
     */
    public function receivedByCompany(array $filters): BaseCollection
    {
        return CompanyPayment::query()
            ->when($filters['company_id'] ?? null, fn (Builder $q, $v) => $q->where('company_id', $v))
            ->when($from = $this->day($filters['from'] ?? null), fn (Builder $q) => $q->where('payment_date', '>=', $from))
            ->when($to = $this->day($filters['to'] ?? null), fn (Builder $q) => $q->where('payment_date', '<=', $to))
            ->groupBy('company_id')
            ->selectRaw('company_id, COALESCE(SUM(amount_received), 0) as total')
            ->toBase()
            ->get()
            ->pluck('total', 'company_id')
            ->map(fn ($total): float => (float) $total);
    }

    /**
     * Commission earned on Paid loads that no invoice has billed yet, per company.
     *
     * @param  array<string, mixed>  $filters
     * @return BaseCollection<int|string, float>
     */
    public function unbilledByCompany(array $filters): BaseCollection
    {
        return Load::query()
            ->whereNotNull('company_id')
            ->where('status', LoadStatus::Paid)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('invoice_lines')->whereColumn('invoice_lines.load_id', 'loads.id'))
            ->when($filters['company_id'] ?? null, fn (Builder $q, $v) => $q->where('company_id', $v))
            ->groupBy('company_id')
            ->selectRaw('company_id, COALESCE(SUM(company_commission_amount), 0) as total')
            ->toBase()
            ->get()
            ->pluck('total', 'company_id')
            ->map(fn ($total): float => (float) $total);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Company>
     */
    public function companies(array $filters): Collection
    {
        return Company::query()
            ->when($filters['company_id'] ?? null, fn (Builder $q, $v) => $q->whereKey($v))
            ->orderBy('name')
            ->get(['id', 'name', 'mc_number']);
    }

    /**
     * Every receipt in the window: what the company owed when it paid, what
     * arrived, and what is still short.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, CompanyPayment>
     */
    public function receipts(array $filters): Collection
    {
        $status = ($filters['status'] ?? '') !== '' ? CompanyPaymentStatus::tryFrom((string) $filters['status']) : null;

        return CompanyPayment::query()
            // Whole relations: the row shows the conversion date for locked
            // payments, and a column list would hand the view a null there.
            ->with(['company:id,name,mc_number', 'invoice:id,invoice_number'])
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->when($filters['company_id'] ?? null, fn (Builder $q, $v) => $q->where('company_id', $v))
            ->when($from = $this->day($filters['from'] ?? null), fn (Builder $q) => $q->where('payment_date', '>=', $from))
            ->when($to = $this->day($filters['to'] ?? null), fn (Builder $q) => $q->where('payment_date', '<=', $to))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Every USD→PKR conversion with the rate used and where the net landed —
     * the log you reconcile exchange-rate drift against.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, CompanyPayment>
     */
    public function conversions(array $filters): Collection
    {
        return CompanyPayment::query()
            ->whereNotNull('converted_at')
            ->with(['company:id,name', 'depositAccount:id,name'])
            ->when($filters['company_id'] ?? null, fn (Builder $q, $v) => $q->where('company_id', $v))
            ->when($from = $this->day($filters['from'] ?? null), fn (Builder $q) => $q->where('conversion_date', '>=', $from))
            ->when($to = $this->day($filters['to'] ?? null), fn (Builder $q) => $q->where('conversion_date', '<=', $to))
            ->orderByDesc('conversion_date')
            ->orderByDesc('id')
            ->get();
    }

    /* ──────────────────────── Accounts & expenses ──────────────────────── */

    /**
     * The filtered expense rows every expense breakdown is built on.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Expense>
     */
    private function expenseBase(array $filters): Builder
    {
        return Expense::query()
            ->when($filters['user_id'] ?? null, fn (Builder $q, $v) => $q->where('entered_by', $v))
            ->when($filters['account_id'] ?? null, fn (Builder $q, $v) => $q->where('account_id', $v))
            ->when($filters['expense_category_id'] ?? null, fn (Builder $q, $v) => $q->where('expense_category_id', $v))
            ->when($from = $this->day($filters['from'] ?? null), fn (Builder $q) => $q->where('expense_date', '>=', $from))
            ->when($to = $this->day($filters['to'] ?? null), fn (Builder $q) => $q->where('expense_date', '<=', $to));
    }

    /**
     * Expense totals grouped by category.
     *
     * @param  array<string, mixed>  $filters
     * @return BaseCollection<int, stdClass>
     */
    public function expensesByCategory(array $filters): BaseCollection
    {
        return $this->expenseBase($filters)
            ->groupBy('expense_category_id')
            ->selectRaw('expense_category_id, COALESCE(SUM(amount), 0) as total, COUNT(*) as expenses_count')
            ->toBase()
            ->get();
    }

    /**
     * One breakdown of the filtered expenses: month | account | method | payee.
     *
     * @param  array<string, mixed>  $filters
     * @return BaseCollection<int, stdClass> rows of (label, total, expenses_count)
     */
    public function expensesBy(string $dimension, array $filters): BaseCollection
    {
        $expr = match ($dimension) {
            'month' => "DATE_FORMAT(expenses.expense_date, '%Y-%m')",
            'account' => "COALESCE(accounts.name, 'No account')",
            'method' => "COALESCE(expenses.payment_method, 'n/a')",
            'payee' => "COALESCE(NULLIF(expenses.payee, ''), 'Unspecified')",
        };

        $query = $this->expenseBase($filters)->toBase();

        if ($dimension === 'account') {
            $query->leftJoin('accounts', 'accounts.id', '=', 'expenses.account_id');
        }

        return $query
            ->selectRaw("{$expr} as label, COALESCE(SUM(expenses.amount), 0) as total, COUNT(*) as expenses_count")
            ->groupBy("label")
            ->orderByRaw($dimension === 'month' ? 'label' : 'total DESC')
            ->when($dimension === 'payee', fn ($q) => $q->limit(15))
            ->get();
    }

    /**
     * Ledger for a given Account over a date range.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AccountTransaction>
     */
    public function accountStatement(array $filters): Collection
    {
        $accountId = $filters['account_id'] ?? null;

        if ($accountId === null) {
            return new Collection();
        }

        return AccountTransaction::query()
            ->where('account_id', $accountId)
            ->when($from = $this->day($filters['from'] ?? null), fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to = $this->day($filters['to'] ?? null), fn (Builder $q) => $q->whereDate('created_at', '<=', $to))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function account(int $accountId): ?Account
    {
        return Account::find($accountId);
    }

    /* ─────────────────────── Dispatcher commissions ─────────────────────── */

    /**
     * Per dispatcher: what they earned on the loads in the window, how much of
     * it is paid out vs still payable, and the payouts made inside the window.
     * A reversed commission (cancelled load) is shown apart, never as earnings.
     *
     * @param  array<string, mixed>  $filters
     * @return BaseCollection<int, stdClass>
     */
    public function dispatcherCommissions(array $filters): BaseCollection
    {
        $accrued = CommissionStatus::Accrued->value;
        $paid = CommissionStatus::Paid->value;
        $reversed = CommissionStatus::Reversed->value;

        $earned = Commission::query()
            ->when($filters['user_id'] ?? null, fn (Builder $q, $v) => $q->where('dispatcher_id', $v))
            ->when($from = $this->start($filters['from'] ?? null), fn (Builder $q) => $q->where('calculated_at', '>=', $from))
            ->when($to = $this->end($filters['to'] ?? null), fn (Builder $q) => $q->where('calculated_at', '<=', $to))
            ->groupBy('dispatcher_id')
            ->selectRaw('dispatcher_id')
            ->selectRaw("SUM(CASE WHEN status != '{$reversed}' THEN 1 ELSE 0 END) as loads_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN status != '{$reversed}' THEN margin_amount END), 0) as margin_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = '{$accrued}' THEN commission_amount END), 0) as payable_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = '{$paid}' THEN commission_amount END), 0) as paid_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = '{$reversed}' THEN commission_amount END), 0) as reversed_total")
            ->toBase()
            ->get()
            ->keyBy('dispatcher_id');

        $payouts = CommissionPayout::query()
            ->when($filters['user_id'] ?? null, fn (Builder $q, $v) => $q->where('dispatcher_id', $v))
            ->when($from = $this->day($filters['from'] ?? null), fn (Builder $q) => $q->where('paid_at', '>=', $from))
            ->when($to = $this->day($filters['to'] ?? null), fn (Builder $q) => $q->where('paid_at', '<=', $to))
            ->groupBy('dispatcher_id')
            ->selectRaw('dispatcher_id, COALESCE(SUM(usd_amount), 0) as usd_total, COALESCE(SUM(pkr_amount), 0) as pkr_total, COUNT(*) as payouts_count')
            ->toBase()
            ->get()
            ->keyBy('dispatcher_id');

        return User::query()->toBase()
            ->whereIn('id', $earned->keys()->merge($payouts->keys())->unique()->values())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (stdClass $user): stdClass => (object) [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'loads_count' => (int) ($earned[$user->id]->loads_count ?? 0),
                'margin_total' => (float) ($earned[$user->id]->margin_total ?? 0),
                'earned_total' => round((float) ($earned[$user->id]->payable_total ?? 0) + (float) ($earned[$user->id]->paid_total ?? 0), 2),
                'paid_total' => (float) ($earned[$user->id]->paid_total ?? 0),
                'payable_total' => (float) ($earned[$user->id]->payable_total ?? 0),
                'reversed_total' => (float) ($earned[$user->id]->reversed_total ?? 0),
                'payouts_count' => (int) ($payouts[$user->id]->payouts_count ?? 0),
                'payouts_usd' => (float) ($payouts[$user->id]->usd_total ?? 0),
                'payouts_pkr' => (float) ($payouts[$user->id]->pkr_total ?? 0),
            ])
            ->values();
    }

    /* ───────────────────────── Profit & loss ───────────────────────── */

    /**
     * What the company has billed (company commission on Paid loads) minus what
     * it has actually spent, over the period. Driver pay is not a line here — it
     * was never ours to pay. Dispatcher commission is a staff cost, shown in its
     * own report rather than deducted here.
     *
     * `gross_rate` is the customer-facing load rate the commission was taken
     * from, kept for context.
     *
     * @param  array<string, mixed>  $filters
     * @return array{revenue: float, gross_rate: float, expenses: float, net_profit: float}
     */
    public function profitAndLoss(array $filters): array
    {
        $loadRow = (array) Load::query()
            ->where('status', LoadStatus::Paid)
            ->when($filters['user_id'] ?? null, fn (Builder $q, $v) => $q->where('dispatcher_id', $v))
            ->when($from = $this->start($filters['from'] ?? null), fn (Builder $q) => $q->where('pickup_at', '>=', $from))
            ->when($to = $this->end($filters['to'] ?? null), fn (Builder $q) => $q->where('pickup_at', '<=', $to))
            ->selectRaw('COALESCE(SUM(company_commission_amount), 0) as revenue, COALESCE(SUM(rate), 0) as gross_rate')
            ->toBase()
            ->first();

        $expenseTotal = (float) Expense::query()
            ->when($filters['user_id'] ?? null, fn (Builder $q, $v) => $q->where('entered_by', $v))
            ->when($from = $this->day($filters['from'] ?? null), fn (Builder $q) => $q->where('expense_date', '>=', $from))
            ->when($to = $this->day($filters['to'] ?? null), fn (Builder $q) => $q->where('expense_date', '<=', $to))
            ->sum('amount');

        $revenue = (float) ($loadRow['revenue'] ?? 0);

        return [
            'revenue' => $revenue,
            'gross_rate' => round((float) ($loadRow['gross_rate'] ?? 0), 2),
            'expenses' => $expenseTotal,
            'net_profit' => round($revenue - $expenseTotal, 2),
        ];
    }

    /* ───────────────────────────── helpers ───────────────────────────── */

    /** A valid Y-m-d, or null. For `date` columns. */
    private function day(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' && Carbon::hasFormat($value, 'Y-m-d') ? $value : null;
    }

    /** Start of that day in the entry zone, as the UTC instant to compare timestamps with. */
    private function start(mixed $value): ?\Carbon\Carbon
    {
        $day = $this->day($value);

        return $day === null ? null : Timezones::parseUs($day.' 00:00:00');
    }

    /** End of that day in the entry zone, as a UTC instant. */
    private function end(mixed $value): ?\Carbon\Carbon
    {
        $day = $this->day($value);

        return $day === null ? null : Timezones::parseUs($day.' 23:59:59');
    }
}
