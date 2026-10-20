<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\StoreCommissionPayoutRequest;
use App\Models\Account;
use App\Models\User;
use App\Services\Commission\CommissionPayoutService;
use App\Services\Commission\CommissionService;
use App\Services\Ledger\LedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CommissionPayoutController extends Controller
{
    public function __construct(
        private readonly CommissionPayoutService $payouts,
        private readonly CommissionService $commissions,
        private readonly LedgerService $ledger,
    ) {
    }

    /**
     * Payout form. Separate from the expense form on purpose: an expense is
     * The company's own overhead in PKR, this is somebody's earned cut being
     * converted out of USD and handed over.
     */
    public function create(Request $request): View
    {
        $dispatcher = User::query()->find($request->integer('dispatcher_id'));
        $accounts = Account::query()->where('status', 'active')->orderBy('name')->get();

        return view('commissions.payout', [
            'dispatcher' => $dispatcher,
            'dispatchers' => $this->commissions->dispatcherPositions($request->user())->pluck('user'),
            'position' => $dispatcher ? $this->ledger->position($dispatcher) : null,
            'accounts' => $accounts,
            'balances' => $accounts->pluck('current_balance', 'id')->all(),
            'rate' => $this->payouts->lastRate() ?? $this->commissions->latestRate(),
        ]);
    }

    public function store(StoreCommissionPayoutRequest $request): RedirectResponse
    {
        $dispatcher = User::query()->findOrFail($request->integer('dispatcher_id'));

        $payout = $this->payouts->create($dispatcher, $request->validated(), $request->user());

        return redirect()
            ->route('commissions.ledger', $dispatcher)
            ->with('success', "Paid \${$payout->usd_amount} (Rs {$payout->pkr_amount}) to {$dispatcher->name}.");
    }
}
