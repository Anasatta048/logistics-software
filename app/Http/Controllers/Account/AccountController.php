<?php

namespace App\Http\Controllers\Account;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreAccountRequest;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Models\Account;
use App\Services\Account\AccountService;
use App\Services\Account\MoneyFlowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AccountController extends Controller
{
    public function __construct(private readonly AccountService $accounts)
    {
    }

    public function index(): View
    {
        return view('accounts.index', [
            'accounts' => Account::query()->orderBy('name')->paginate(500),
            'cash' => (float) Account::query()->where('status', 'active')->where('type', 'cash')->sum('current_balance'),
            'bank' => (float) Account::query()->where('status', 'active')->where('type', 'bank')->sum('current_balance'),
        ]);
    }

    public function create(): View
    {
        return view('accounts.create', [
            'types' => AccountType::cases(),
            'statuses' => AccountStatus::cases(),
        ]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $account = $this->accounts->create($request->validated());

        return redirect()->route('accounts.index')->with('success', "Account {$account->name} created.");
    }

    public function edit(Account $account): View
    {
        return view('accounts.edit', [
            'account' => $account,
            'types' => AccountType::cases(),
            'statuses' => AccountStatus::cases(),
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $this->accounts->update($account, $request->validated());

        return redirect()->route('accounts.index')->with('success', "Account {$account->name} updated.");
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->accounts->delete($account);

        return redirect()->route('accounts.index')->with('success', 'Account removed.');
    }

    public function ledger(Request $request, Account $account, MoneyFlowService $flow): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        // Default to the current month so the screen stays fast on a busy account.
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? null;

        return view('accounts.ledger', [
            'account' => $account,
            'statement' => $flow->statement($account, $from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }
}
