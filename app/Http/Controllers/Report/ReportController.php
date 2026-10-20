<?php

namespace App\Http\Controllers\Report;

use App\Enums\CompanyPaymentStatus;
use App\Enums\LoadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ReportFilterRequest;
use App\Models\Account;
use App\Models\Broker;
use App\Models\Company;
use App\Models\Driver;
use App\Models\User;
use App\Services\Report\ReportService;
use App\Support\ReportExport;
use App\Support\Timezones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * One thin method per report: validate filters via the Form Request, authorize
 * via the named `reports.{report}` Gate on the route, delegate to ReportService
 * and render. No query or business logic here.
 */
final class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function index(): View
    {
        return view('reports.index');
    }

    /* ───────────────────────────── Loads ───────────────────────────── */

    public function loads(ReportFilterRequest $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $viewer = $request->user();
        $report = $this->reports->loads($request->validated(), $viewer);

        if ($request->wantsExport()) {
            $withCommission = $viewer->can('view-company-commission');
            $zone = Timezones::usLabel();

            return $this->export($request, 'Load register', 'load-register',
                array_filter(['Reference', 'Status', 'Company', 'Broker', 'Driver', 'Dispatcher', "Pickup ({$zone})", 'Origin', 'Destination', 'Miles', 'Rate', $withCommission ? 'Commission' : null, 'Cancelled by', "Cancelled at ({$zone})", 'Cancellation reason']),
                $report['rows']->map(fn ($load): array => array_filter([
                    'reference' => $load->reference_number,
                    'status' => $load->status->label(),
                    'company' => $load->company?->name,
                    'broker' => $load->broker?->company_name,
                    'driver' => $load->driver?->name ?? $load->driver_name,
                    'dispatcher' => $load->dispatcher?->name,
                    'pickup' => Timezones::toUs($load->pickup_at)?->format('Y-m-d H:i'),
                    'origin' => $load->origin_address,
                    'destination' => $load->destination_address,
                    'miles' => $load->total_miles,
                    'rate' => $load->rate,
                    'commission' => $withCommission ? $load->company_commission_amount : null,
                    'cancelled_by' => $load->cancelledBy?->name,
                    'cancelled_at' => Timezones::toUs($load->cancelled_at)?->format('Y-m-d H:i'),
                    'reason' => $load->cancellation_reason,
                ], fn ($value, $key): bool => $key !== 'commission' || $withCommission, ARRAY_FILTER_USE_BOTH)),
            );
        }

        return view('reports.loads', [
            ...$report,
            'statuses' => LoadStatus::cases(),
            ...$this->lookups($viewer, companies: true, brokers: true, drivers: true, users: true),
        ]);
    }

    public function drivers(ReportFilterRequest $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $viewer = $request->user();
        $report = $this->reports->drivers($request->validated(), $viewer);

        if ($request->wantsExport()) {
            $withCommission = $viewer->can('view-company-commission');

            return $this->export($request, 'Driver earnings', 'driver-earnings',
                array_values(array_filter(['Driver', 'Truck', 'Company', 'Loads', 'Delivered / paid', 'Cancelled', 'Miles', 'Gross rate', 'Rate / mile', $withCommission ? 'Company commission' : null, 'Last pickup'])),
                $report['rows']->map(fn ($row): array => array_values(array_filter([
                    $row->driver_name, $row->truck_number, $row->company_name, $row->loads_count, $row->completed_count, $row->cancelled_count,
                    $row->miles_total, $row->rate_total, $row->rate_per_mile,
                    $withCommission ? $row->commission_total : false,
                    $row->last_pickup ? Timezones::toUs(\Illuminate\Support\Carbon::parse($row->last_pickup, 'UTC'))?->format('Y-m-d') : null,
                ], fn ($value): bool => $value !== false))),
            );
        }

        return view('reports.drivers', [
            ...$report,
            ...$this->lookups($viewer, companies: true, drivers: true, users: true),
        ]);
    }

    public function brokers(ReportFilterRequest $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $report = $this->reports->brokers($request->validated(), $request->user());

        if ($request->wantsExport()) {
            return $this->export(
                $request, 'Broker performance', 'broker-performance',
                ['Broker', 'Loads', 'Miles', 'Paid revenue', 'Cancelled', 'Cancellation rate %'],
                $report['rows']->map(fn ($row): array => [$row->company_name, $row->loads_count, $row->miles_total, $row->revenue_total, $row->cancelled_count, $row->cancellation_rate]),
                ['Total loads' => $report['totals']['loads'], 'Paid revenue' => $report['totals']['revenue'], 'Cancelled' => $report['totals']['cancelled']],
            );
        }

        return view('reports.brokers', [
            ...$report,
            ...$this->lookups($request->user(), brokers: true),
        ]);
    }

    /* ────────────────────── Invoices & payments ────────────────────── */

    public function invoices(ReportFilterRequest $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $report = $this->reports->invoices($request->validated());

        if ($request->wantsExport()) {
            return $this->export($request, 'Invoice register', 'invoice-register',
                ['Invoice #', 'Company', 'Driver', 'Invoice date', 'Due date', 'Period', 'Billed', 'Paid', 'Balance', 'Status', 'Days overdue'],
                $report['rows']->map(fn ($row): array => [
                    $row->invoice->invoice_number,
                    $row->invoice->company?->name,
                    $row->invoice->driver?->name ?? $row->invoice->driver_name,
                    $row->invoice->invoice_date->toDateString(),
                    $row->invoice->due_date->toDateString(),
                    $row->invoice->period_from->toDateString().' to '.$row->invoice->period_to->toDateString(),
                    $row->due,
                    $row->paid,
                    $row->balance,
                    $row->overdue ? 'Overdue' : $row->status,
                    $row->days_overdue,
                ]),
            );
        }

        return view('reports.invoices', [
            ...$report,
            'states' => ['open' => 'Open (balance due)', 'overdue' => 'Overdue', 'pending' => 'Pending', 'partially_settled' => 'Partially settled', 'fully_settled' => 'Fully settled', 'carried' => 'Carried forward'],
            ...$this->lookups($request->user(), companies: true, drivers: true),
        ]);
    }

    public function receivables(ReportFilterRequest $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $report = $this->reports->receivables($request->validated());

        if ($request->wantsExport()) {
            return $this->export($request, 'Company receivables', 'company-receivables',
                ['Company', 'MC #', 'Invoices', 'Billed', 'Received', 'Balance due', 'Overdue', 'Credit held', 'Commission not yet billed'],
                $report['rows']->map(fn ($row): array => [
                    $row->company_name, $row->mc_number, $row->invoices_count, $row->billed_total, $row->received_total,
                    $row->balance_total, $row->overdue_total, $row->credit_total, $row->unbilled_total,
                ]),
            );
        }

        return view('reports.receivables', [
            ...$report,
            ...$this->lookups($request->user(), companies: true),
        ]);
    }

    public function receipts(ReportFilterRequest $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $report = $this->reports->receipts($request->validated());

        if ($request->wantsExport()) {
            return $this->export($request, 'Payment receipts', 'payment-receipts',
                ['Receipt #', 'Date', 'Company', 'Invoice #', 'Method', 'Reference', 'Owed at the time', 'Received', 'Shortfall', 'Status', 'Converted on'],
                $report['rows']->map(fn ($payment): array => [
                    $payment->id,
                    $payment->payment_date->toDateString(),
                    $payment->company?->name,
                    $payment->invoice?->invoice_number,
                    $payment->payment_method?->label(),
                    $payment->reference,
                    $payment->total_due,
                    $payment->amount_received,
                    $payment->shortfall(),
                    $payment->status->label(),
                    $payment->conversion_date?->toDateString(),
                ]),
            );
        }

        return view('reports.receipts', [
            ...$report,
            'statuses' => CompanyPaymentStatus::cases(),
            ...$this->lookups($request->user(), companies: true),
        ]);
    }

    public function conversions(ReportFilterRequest $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $report = $this->reports->conversions($request->validated());

        if ($request->wantsExport()) {
            return $this->export($request, 'PKR conversions', 'pkr-conversions',
                ['Date', 'Company', 'USD', 'Rate', 'PKR gross', 'Tax', 'PKR net', 'Deposited to'],
                $report['rows']->map(fn ($conversion): array => [
                    $conversion->conversion_date->toDateString(),
                    $conversion->company?->name,
                    $conversion->converted_usd,
                    $conversion->conversion_rate,
                    $conversion->pkr_gross,
                    $conversion->tax_amount,
                    $conversion->pkr_net,
                    $conversion->depositAccount?->name,
                ]),
            );
        }

        return view('reports.conversions', [
            ...$report,
            ...$this->lookups($request->user(), companies: true),
        ]);
    }

    /* ──────────────────────── Accounts & expenses ──────────────────────── */

    public function expenses(ReportFilterRequest $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $report = $this->reports->expenses($request->validated(), $request->user());

        if ($request->wantsExport()) {
            return $this->export($request, 'Expenses by category', 'expenses-by-category',
                ['Category', 'Entries', 'Total', 'Share %'],
                collect($report['rows'])->map(fn (array $row): array => [$row['label'], $row['count'], $row['total'], $row['share']]),
            );
        }

        return view('reports.expenses', $report);
    }

    public function accountStatement(ReportFilterRequest $request): View|\Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        $accounts = Account::query()->orderBy('name')->get(['id', 'name']);
        $accountId = $request->validated('account_id') ?? $accounts->first()?->id;

        if ($accountId === null) {
            return redirect()->route('reports.index')->with('error', 'No accounts exist yet.');
        }

        $transactions = $this->reports->accountStatement([...$request->validated(), 'account_id' => $accountId]);

        if ($request->wantsExport()) {
            return $this->export($request, 'Account statement', 'account-statement', ['Date', 'Type', 'Amount', 'Running Balance', 'Description'], $transactions->map(fn ($t): array => [
                $t->created_at->toDateTimeString(),
                $t->type->label(),
                $t->amount,
                $t->running_balance,
                $t->description,
            ]));
        }

        return view('reports.account-statement', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'selectedAccountId' => $accountId,
        ]);
    }

    /* ─────────────────────── Commissions & summary ─────────────────────── */

    public function dispatcherCommissions(ReportFilterRequest $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $viewer = $request->user();
        $report = $this->reports->dispatcherCommissions($request->validated(), $viewer);

        if ($request->wantsExport()) {
            return $this->export($request, 'Dispatcher commissions', 'dispatcher-commissions',
                ['Dispatcher', 'Loads', 'Margin', 'Commission earned', 'Already paid', 'Still payable', 'Reversed', 'Payouts in period (USD)', 'Payouts in period (PKR)'],
                $report['rows']->map(fn ($row): array => [
                    $row->user_name, $row->loads_count, $row->margin_total, $row->earned_total, $row->paid_total,
                    $row->payable_total, $row->reversed_total, $row->payouts_usd, $row->payouts_pkr,
                ]),
            );
        }

        return view('reports.dispatcher-commissions', [
            ...$report,
            ...$this->lookups($viewer, users: true),
        ]);
    }

    public function profitAndLoss(ReportFilterRequest $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $totals = $this->reports->profitAndLoss($request->validated(), $request->user());

        if ($request->wantsExport()) {
            return $this->export(
                $request, 'Profit and loss', 'profit-and-loss',
                ['Line', 'Amount (USD)'],
                [['Commission billed', $totals['revenue']], ['Gross rate', $totals['gross_rate']], ['Expenses', $totals['expenses']], ['Net profit', $totals['net_profit']], ['Net margin %', $totals['margin_percentage']]],
            );
        }

        return view('reports.profit-and-loss', [
            'totals' => $totals,
        ]);
    }

    /* ───────────────────────────── helpers ───────────────────────────── */

    /**
     * Dropdown contents for the filter bar. The dispatcher picker is only
     * offered to people who are not already limited to their own records.
     *
     * @return array<string, Collection<int, mixed>|null>
     */
    private function lookups(User $viewer, bool $companies = false, bool $brokers = false, bool $drivers = false, bool $users = false): array
    {
        return [
            'companies' => $companies ? Company::query()->visibleTo($viewer)->orderBy('name')->get(['id', 'name']) : null,
            'brokers' => $brokers ? Broker::query()->orderBy('company_name')->get(['id', 'company_name']) : null,
            'drivers' => $drivers ? Driver::query()->visibleTo($viewer)->orderBy('name')->get(['id', 'name']) : null,
            'users' => $users && ! $viewer->isScopedToOwnRecords() ? User::query()->orderBy('name')->get(['id', 'name']) : null,
        ];
    }

    /**
     * CSV or PDF, whichever the filter bar asked for, with the report's
     * logo / period / filter header (see ReportExport).
     *
     * @param  list<string>  $headings
     * @param  iterable<int, array<int|string, mixed>>  $rows
     * @param  array<string, string|int|float>  $summary
     */
    private function export(ReportFilterRequest $request, string $title, string $filename, array $headings, iterable $rows, array $summary = []): \Symfony\Component\HttpFoundation\Response
    {
        return ReportExport::respond((string) $request->validated('export'), $title, $filename, $headings, $rows, $request->validated(), $summary);
    }
}
