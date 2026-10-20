<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountTransfer;
use App\Services\Account\AccountTransferService;
use App\Support\Tenancy\TenantRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AccountTransferController extends Controller
{
    public function __construct(private readonly AccountTransferService $transfers)
    {
    }

    public function index(): View
    {
        return view('account-transfers.index', [
            'transfers' => AccountTransfer::query()->with(['from:id,name', 'to:id,name', 'author:id,name'])->latest('transfer_date')->latest('id')->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('account-transfers.create', [
            'accounts' => Account::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'current_balance']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_account_id' => ['required', 'integer', TenantRule::exists('accounts', 'id')->where('status', 'active')],
            'to_account_id' => ['required', 'integer', 'different:from_account_id', TenantRule::exists('accounts', 'id')->where('status', 'active')],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'fee' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'transfer_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:60'],
            'narration' => ['nullable', 'string', 'max:255'],
        ]);

        $transfer = $this->transfers->create($data, $request->user());

        return redirect()->route('account-transfers.index')
            ->with('success', 'Moved '.cur_local().' '.number_format((float) $transfer->amount, 2)." from {$transfer->from->name} to {$transfer->to->name}.");
    }

    public function reverse(Request $request, AccountTransfer $accountTransfer): RedirectResponse
    {
        $this->transfers->reverse($accountTransfer, $request->user());

        return back()->with('success', "Transfer #{$accountTransfer->id} reversed.");
    }
}
