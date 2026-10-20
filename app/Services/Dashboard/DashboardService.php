<?php

namespace App\Services\Dashboard;

use App\Enums\DriverStatus;
use App\Enums\LoadStatus;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Load;
use App\Models\User;
use App\Services\Company\CompanyPaymentService;
use App\Services\Report\ReportService;
use Illuminate\Support\Carbon;

/**
 * Aggregates the numbers the post-login landing page shows. Reuses
 * ReportService for the margin figure instead of duplicating that query
 * (Phase 9 note) — the only thing computed directly here is the load-status
 * board, neither of which is a report.
 */
final class DashboardService
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly CompanyPaymentService $payments,
    ) {
    }

    /**
     * @return array{
     *     active_by_status: array<string, int>,
     *     month_margin: float,
     *     month_commission: float,
     *     pending_settlement: float,
     *     available_drivers: int,
     *     today_bookings: int,
     *     status_counts: array<string, int>,
     *     needs_attention: \Illuminate\Support\Collection<int, Load>,
     *     trend: array<int, array{date: string, revenue: float, margin: float}>,
     * }
     */
    public function forUser(User $viewer): array
    {
        $activeStatuses = [LoadStatus::Booked, LoadStatus::Pickup, LoadStatus::Loaded];

        $activeByStatus = [];
        foreach ($activeStatuses as $status) {
            $activeByStatus[$status->value] = $this->loadQuery($viewer)
                ->where('status', $status)
                ->count();
        }

        // Anyone but an admin sees their own commission only, whatever their role.
        $commissions = $this->reports->dispatcherCommissions(array_filter([
            'from' => Carbon::now()->startOfMonth()->toDateString(),
            'to' => Carbon::now()->endOfMonth()->toDateString(),
            'user_id' => $viewer->hasRole('admin') ? null : $viewer->id,
        ]), $viewer)['totals'];

        // What companies still owe: every paid load's commission, less what has
        // been received, less anything they paid ahead. The ledger
        // balance each company's payment screen quotes, summed.
        $pendingSettlement = (float) Company::query()
            ->visibleTo($viewer)
            ->where('status', 'active')
            ->get()
            ->sum(fn (Company $company): float => $this->payments->balance($company)['outstanding']);

        return [
            'active_by_status' => $activeByStatus,
            'month_margin' => $commissions['margin'],
            // Earned this month: what is still payable plus what was already paid out.
            'month_commission' => $commissions['earned'],
            'pending_settlement' => (float) $pendingSettlement,
            'available_drivers' => Driver::query()->visibleTo($viewer)->where('status', DriverStatus::Available)->count(),
            'today_bookings' => $this->loadQuery($viewer)->whereDate('created_at', Carbon::today())->count(),
            'status_counts' => $this->statusCounts($viewer),
            'needs_attention' => $this->needsAttention($viewer),
            'trend' => $this->trend($viewer),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(User $viewer): array
    {
        $counts = [];

        foreach (LoadStatus::cases() as $status) {
            $counts[$status->value] = $this->loadQuery($viewer)->where('status', $status)->count();
        }

        return $counts;
    }

    /**
     * Loads with an overdue pickup that are not delivered yet, per
     * Section 3's "Needs Attention" panel — kept to a single additive query,
     * not a rewrite of the dashboard service.
     *
     * @return \Illuminate\Support\Collection<int, Load>
     */
    private function needsAttention(User $viewer): \Illuminate\Support\Collection
    {
        return $this->loadQuery($viewer)
            ->whereIn('status', [LoadStatus::Booked, LoadStatus::Pickup, LoadStatus::Loaded])
            ->where('pickup_at', '<', Carbon::now())
            ->orderBy('pickup_at')
            ->limit(8)
            ->get();
    }

    /**
     * Revenue & margin for the last 14 days (Section 3's dashboard chart).
     * Deliberately a self-contained query over `loads` directly (revenue = the
     * load's rate, margin = the company commission Dreamelect earns on it)
     * rather than extending ReportRepository — keeps this purely a
     * dashboard-chart concern, not a new report.
     *
     * @return array<int, array{date: string, revenue: float, margin: float}>
     */
    private function trend(User $viewer): array
    {
        $from = Carbon::now()->subDays(13)->startOfDay();
        $to = Carbon::now()->endOfDay();

        $rows = $this->loadQuery($viewer)
            ->where('status', LoadStatus::Paid)
            ->whereBetween('pickup_at', [$from, $to])
            ->selectRaw('DATE(pickup_at) as day')
            ->selectRaw('COALESCE(SUM(rate), 0) as revenue_total')
            ->selectRaw('COALESCE(SUM(company_commission_amount), 0) as margin_total')
            ->groupBy('day')
            ->toBase()
            ->get()
            ->keyBy(fn (\stdClass $row) => (string) $row->day);

        $days = [];
        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $row = $rows->get($date->toDateString());

            $days[] = [
                'date' => $date->format('M j'),
                'revenue' => (float) ($row->revenue_total ?? 0),
                'margin' => (float) ($row->margin_total ?? 0),
            ];
        }

        return $days;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Load>
     */
    private function loadQuery(User $viewer): \Illuminate\Database\Eloquent\Builder
    {
        return Load::query()
            ->when(! $viewer->hasRole('admin'), fn ($query) => $query->where('dispatcher_id', $viewer->id));
    }
}
