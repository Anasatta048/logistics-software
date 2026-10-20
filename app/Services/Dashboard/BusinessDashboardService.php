<?php

namespace App\Services\Dashboard;

use App\Enums\CommissionStatus;
use App\Enums\LoadStatus;
use App\Models\Account;
use App\Models\Broker;
use App\Models\Commission;
use App\Models\CommissionPayout;
use App\Models\Company;
use App\Models\CompanyPayment;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Load;
use App\Models\Setting;
use App\Models\User;
use App\Services\Tenancy\TenantLimits;
use App\Support\Settings\TenantSettings;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * The owner's view of the business: money, receivables, spending, alerts, and
 * a "get set up" checklist for a new workspace. Every block is shown only to
 * someone holding the matching right, and the whole money block is cached for
 * a minute per workspace so the dashboard stays quick as data grows.
 *
 * Amounts: USD for what clients owe / paid (fees, receivables), PKR for what
 * sits in accounts and what the office spends.
 */
final class BusinessDashboardService
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly TenantLimits $limits,
    ) {
    }

    /**
     * @return array{finance: ?array<string, mixed>, alerts: list<array{tone: string, text: string, url: ?string}>, onboarding: ?array<string, mixed>, usage: list<array{label: string, used: int, limit: ?int}>}
     */
    public function forUser(User $viewer, bool $fresh = false): array
    {
        $tenantId = (int) $this->context->id();
        $money = $viewer->can('view-company-commission');

        $finance = null;
        $alerts = [];

        if ($money) {
            // Blocks the viewer may not see are left out, so the cache is split by those rights.
            $key = "dashboard.finance.{$tenantId}.".(int) $viewer->can('expenses.view').(int) $viewer->can('accounts.view');
            $fresh && Cache::forget($key);
            $finance = Cache::remember($key, 60, fn (): array => $this->finance($viewer));
            $alerts = $this->alerts($viewer, $finance);
        }

        return [
            'finance' => $finance,
            'alerts' => $alerts,
            'onboarding' => $viewer->can('settings.manage') ? $this->onboarding($tenantId) : null,
            'usage' => $viewer->can('settings.manage') ? $this->usage() : [],
        ];
    }

    /** @return array<string, mixed> */
    private function finance(User $viewer): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = $monthStart->copy()->subMonth();

        $loads = Load::query()
            ->where('status', '!=', LoadStatus::Cancelled)
            ->where('pickup_at', '>=', $monthStart)
            ->selectRaw('COUNT(*) as loads, COALESCE(SUM(rate), 0) as gross')
            ->toBase()->first();

        $fees = (float) Load::query()->where('status', LoadStatus::Paid)->where('pickup_at', '>=', $monthStart)->sum('company_commission_amount');

        $canExpenses = $viewer->can('expenses.view');
        $expenses = $canExpenses ? (float) Expense::query()->where('expense_date', '>=', $monthStart->toDateString())->sum('amount') : null;
        $lastExpenses = $canExpenses ? (float) Expense::query()->whereBetween('expense_date', [$lastMonthStart->toDateString(), $monthStart->copy()->subDay()->toDateString()])->sum('amount') : null;

        $converted = (float) CompanyPayment::query()->whereNotNull('converted_at')->where('conversion_date', '>=', $monthStart->toDateString())->sum('pkr_net');
        $payouts = (float) CommissionPayout::query()->where('paid_at', '>=', $monthStart->toDateString())->sum('pkr_amount');

        return [
            'month' => $monthStart->format('F Y'),
            'loads' => (int) ($loads->loads ?? 0),
            'gross' => (float) ($loads->gross ?? 0),
            'fees' => $fees,
            'expenses' => $expenses,
            'expenses_change' => $lastExpenses ? round(($expenses - $lastExpenses) / $lastExpenses * 100) : null,
            'converted_pkr' => $converted,
            'payouts_pkr' => $payouts,
            'net_pkr' => $expenses === null ? null : round($converted - $expenses - $payouts, 2),
            'cash' => $viewer->can('accounts.view') ? $this->cash() : null,
            'unconverted_usd' => (float) CompanyPayment::query()->whereNull('converted_at')->where('amount_received', '>', 0)->sum('amount_received'),
            'commission_payable_usd' => (float) Commission::query()->where('status', CommissionStatus::Accrued)->sum('commission_amount'),
            'overdue' => $this->overdueInvoices(),
            'months' => $canExpenses ? $this->sixMonths() : [],
            'top_categories' => $canExpenses ? $this->topCategories($monthStart) : [],
        ];
    }

    /** @return array{total: float, low: list<array{name: string, balance: float}>} */
    private function cash(): array
    {
        $accounts = Account::query()->where('status', 'active')->get(['name', 'current_balance']);
        $threshold = (float) TenantSettings::get('operations.low_balance_alert', 0);

        return [
            'total' => round((float) $accounts->sum('current_balance'), 2),
            'low' => $threshold > 0
                ? $accounts->filter(fn (Account $a): bool => (float) $a->current_balance < $threshold)
                    ->map(fn (Account $a): array => ['name' => $a->name, 'balance' => (float) $a->current_balance])->values()->all()
                : [],
        ];
    }

    /** @return array{count: int, amount: float} */
    private function overdueInvoices(): array
    {
        $paid = '(SELECT COALESCE(SUM(cp.amount_received), 0) FROM company_payments cp WHERE cp.invoice_id = invoices.id AND cp.deleted_at IS NULL)';
        $due = '(invoices.total + invoices.previous_balance - invoices.credit_applied)';
        $graceDays = (int) TenantSettings::get('operations.invoice_overdue_alert_days', 0);

        $row = Invoice::query()
            ->whereNull('carried_to_id')
            ->where('due_date', '<', Carbon::today()->subDays($graceDays))
            ->whereRaw("{$due} > {$paid} + 0.005")
            ->selectRaw("COUNT(*) as c, COALESCE(SUM({$due} - {$paid}), 0) as amount")
            ->toBase()->first();

        return ['count' => (int) ($row->c ?? 0), 'amount' => round((float) ($row->amount ?? 0), 2)];
    }

    /**
     * Income (PKR actually received) against spending, for the last six months.
     *
     * @return list<array{label: string, income: float, spent: float}>
     */
    private function sixMonths(): array
    {
        $from = Carbon::now()->startOfMonth()->subMonths(5);

        $income = CompanyPayment::query()->whereNotNull('converted_at')->where('conversion_date', '>=', $from->toDateString())
            ->selectRaw("DATE_FORMAT(conversion_date, '%Y-%m') as ym, COALESCE(SUM(pkr_net), 0) as total")
            ->groupBy('ym')->toBase()->get()->pluck('total', 'ym');

        $spent = Expense::query()->where('expense_date', '>=', $from->toDateString())
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as ym, COALESCE(SUM(amount), 0) as total")
            ->groupBy('ym')->toBase()->get()->pluck('total', 'ym');

        $months = [];
        for ($m = $from->copy(); $m->lte(Carbon::now()); $m->addMonth()) {
            $ym = $m->format('Y-m');
            $months[] = ['label' => $m->format('M'), 'income' => (float) ($income[$ym] ?? 0), 'spent' => (float) ($spent[$ym] ?? 0)];
        }

        return $months;
    }

    /** @return list<array{name: string, total: float}> */
    private function topCategories(Carbon $monthStart): array
    {
        return Expense::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->where('expenses.expense_date', '>=', $monthStart->toDateString())
            ->selectRaw('expense_categories.name as name, SUM(expenses.amount) as total')
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->limit(6)
            ->toBase()->get()
            ->map(fn ($r): array => ['name' => (string) $r->name, 'total' => (float) $r->total])->all();
    }

    /**
     * @param  array<string, mixed>  $finance
     * @return list<array{tone: string, text: string, url: ?string}>
     */
    private function alerts(User $viewer, array $finance): array
    {
        $alerts = [];

        if ($finance['overdue']['count'] > 0 && $viewer->can('invoices.view')) {
            $alerts[] = ['tone' => 'danger', 'url' => route('reports.invoices'),
                'text' => $finance['overdue']['count'].' overdue invoice(s), $'.number_format($finance['overdue']['amount'], 2).' outstanding'];
        }

        if ($finance['unconverted_usd'] > 0 && $viewer->can('company-payments.view')) {
            $alerts[] = ['tone' => 'warning', 'url' => route('company-payments.index'),
                'text' => cur_foreign().number_format($finance['unconverted_usd'], 2).' received in USD and not yet converted to PKR'];
        }

        foreach ($finance['cash']['low'] ?? [] as $low) {
            $alerts[] = ['tone' => 'danger', 'url' => route('accounts.index'),
                'text' => "{$low['name']} is low: Rs ".number_format($low['balance'], 2)];
        }

        if ($finance['commission_payable_usd'] > 0 && $viewer->can('commissions.pay')) {
            $alerts[] = ['tone' => 'info', 'url' => route('commissions.index'),
                'text' => cur_foreign().number_format($finance['commission_payable_usd'], 2).' commission owed to dispatchers'];
        }

        return $alerts;
    }

    /**
     * "Get set up" steps for a fresh workspace. Once everything is done the
     * checklist stops being computed at all.
     *
     * @return array{steps: list<array{label: string, done: bool, url: string}>, done: int, total: int}|null
     */
    private function onboarding(int $tenantId): ?array
    {
        if (Cache::get("tenant.{$tenantId}.onboarded")) {
            return null;
        }

        $steps = [
            ['Fill in your business profile', Setting::read('settings.business') !== null, route('settings.business.edit')],
            ['Add a bank or cash account', Account::query()->exists(), route('accounts.create')],
            ['Add a carrier company', Company::query()->exists(), route('companies.create')],
            ['Add a driver', Driver::query()->exists(), route('drivers.create')],
            ['Add a broker', Broker::query()->exists(), route('brokers.create')],
            ['Book your first load', Load::query()->exists(), route('loads.create')],
            ['Send your first invoice', Invoice::query()->exists(), route('invoices.create')],
            ['Record a payment', CompanyPayment::query()->exists(), route('company-payments.create')],
        ];

        $steps = array_map(fn (array $s): array => ['label' => $s[0], 'done' => $s[1], 'url' => $s[2]], $steps);
        $done = count(array_filter($steps, fn (array $s): bool => $s['done']));

        if ($done === count($steps)) {
            Cache::forever("tenant.{$tenantId}.onboarded", true);

            return null;
        }

        return ['steps' => $steps, 'done' => $done, 'total' => count($steps)];
    }

    /** @return list<array{label: string, used: int, limit: ?int}> */
    private function usage(): array
    {
        $rows = [];

        foreach (TenantLimits::RESOURCES as $resource => [, $label]) {
            $limit = $this->limits->limit($resource);
            $rows[] = ['label' => ucfirst($label), 'used' => $this->limits->used($resource), 'limit' => $limit];
        }

        return array_values(array_filter($rows, fn (array $r): bool => $r['limit'] !== null));
    }
}
