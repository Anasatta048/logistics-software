<?php

namespace App\Http\Controllers\Account;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreAccountRequest;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Models\Account;
use App\Services\Account\AccountService;
use Illuminate\Http\RedirectResponse;
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

    public function ledger(Account $account): View
    {
        return view('accounts.ledger', [
            'account' => $account,
            'transactions' => $this->accounts->ledgerFor($account, null, null),
        ]);
    }
}
