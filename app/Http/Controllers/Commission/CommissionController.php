<?php

namespace App\Http\Controllers\Commission;

use App\Enums\CommissionStatus;
use App\Http\Controllers\Controller;
use App\Models\CommissionPayout;
use App\Models\User;
use App\Services\Commission\CommissionService;
use App\Services\Ledger\LedgerService;
use App\Services\Report\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

final class CommissionController extends Controller
{
    public function __construct(
        private readonly CommissionService $commissions,
        private readonly LedgerService $ledger,
        private readonly ReportService $reports,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->only('q', 'status', 'dispatcher_id', 'from', 'to');
        $viewer = $request->user();
        $positions = $this->commissions->dispatcherPositions($viewer);

        // Same date window as the list below, so the totals and the rows agree.
        $period = $this->reports->dispatcherCommissions(array_filter([
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
            'user_id' => $filters['dispatcher_id'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== ''), $viewer)['totals'];

        return view('commissions.index', [
            'period' => $period,
            'commissions' => $this->commissions->paginate($filters, $viewer),
            'statuses' => CommissionStatus::cases(),
            'positions' => $positions,
            // How much of each commission is covered so far, keyed by load.
            'coverage' => $positions->flatMap(fn (object $row) => $row->position['items'])
                ->keyBy('load_id'),
            'dispatchers' => $viewer->isScopedToOwnRecords() ? collect() : $positions->pluck('user'),
        ]);
    }

    /**
     * One dispatcher's statement: what was earned and when, every payout, and
     * whether anything was paid ahead.
     */
    public function ledger(Request $request, User $user): View
    {
        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));

        return view('commissions.ledger', [
            'dispatcher' => $user,
            'position' => $this->ledger->position($user),
            'statement' => $this->ledger->statement($user, $from, $to),
            'payouts' => CommissionPayout::query()
                ->where('dispatcher_id', $user->id)
                ->with(['paymentAccount:id,name', 'paidByUser:id,name'])
                ->latest('paid_at')->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }

    private function date(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        return $value !== '' && Carbon::hasFormat($value, 'Y-m-d') ? Carbon::parse($value) : null;
    }
}
