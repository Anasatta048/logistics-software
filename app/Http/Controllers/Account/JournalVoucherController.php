<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreJournalVoucherRequest;
use App\Models\Account;
use App\Models\JournalVoucher;
use App\Services\Account\JournalVoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class JournalVoucherController extends Controller
{
    public function __construct(private readonly JournalVoucherService $journalVouchers)
    {
    }

    public function index(): View
    {
        return view('journal-vouchers.index', [
            'vouchers' => JournalVoucher::query()
                ->with(['debitAccount', 'creditAccount'])
                ->latest('date')
                ->latest('id')
                ->paginate(500),
        ]);
    }

    public function create(): View
    {
        return view('journal-vouchers.create', [
            'accounts' => Account::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreJournalVoucherRequest $request): RedirectResponse
    {
        $jv = $this->journalVouchers->create($request->validated(), $request->user());

        return redirect()->route('journal-vouchers.index')->with('success', "Journal voucher {$jv->jv_number} created (pending approval).");
    }

    public function approve(Request $request, JournalVoucher $journalVoucher): RedirectResponse
    {
        $this->journalVouchers->approve($journalVoucher, $request->user());

        return redirect()->route('journal-vouchers.index')->with('success', "Journal voucher {$journalVoucher->jv_number} approved.");
    }

    public function void(Request $request, JournalVoucher $journalVoucher): RedirectResponse
    {
        $this->journalVouchers->void($journalVoucher, $request->user());

        return redirect()->route('journal-vouchers.index')->with('success', "Journal voucher {$journalVoucher->jv_number} voided.");
    }
}
