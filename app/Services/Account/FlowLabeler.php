<?php

namespace App\Services\Account;

use App\Enums\TransactionSource;
use App\Models\AccountTransaction;
use App\Models\AccountTransfer;
use App\Models\CommissionPayout;
use App\Models\CompanyPayment;
use App\Models\Expense;
use App\Models\JournalVoucher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Says what each ledger row is in business words and where it came from
 * ("Payment from Acme Carriers", "Expense · Rent, paid to Landlord"), with a
 * link to the document when the viewer may open it. Sources are loaded in one
 * query per type, never one per row.
 */
final class FlowLabeler
{
    /**
     * Sets `flow` = {title, party, url} on every transaction.
     *
     * @param  Collection<int, AccountTransaction>  $transactions
     * @return Collection<int, AccountTransaction>
     */
    public function annotate(Collection $transactions): Collection
    {
        $ids = fn (TransactionSource $source): array => $transactions
            ->filter(fn (AccountTransaction $t): bool => $t->source_type === $source && $t->source_id)
            ->pluck('source_id')->unique()->values()->all();

        $expenses = Expense::query()->with('category:id,name')->whereIn('id', $ids(TransactionSource::Expense))->get()->keyBy('id');
        $payments = CompanyPayment::query()->with('company:id,name')->whereIn('id', $ids(TransactionSource::CurrencyConversion))->get()->keyBy('id');
        $payouts = CommissionPayout::query()->with('dispatcher:id,name')->whereIn('id', $ids(TransactionSource::Commission))->get()->keyBy('id');
        $vouchers = JournalVoucher::query()->with(['debitAccount:id,name', 'creditAccount:id,name'])->whereIn('id', $ids(TransactionSource::JournalVoucher))->get()->keyBy('id');
        $transfers = AccountTransfer::query()->with(['from:id,name', 'to:id,name'])->whereIn('id', $ids(TransactionSource::Transfer))->get()->keyBy('id');

        $canOpen = fn (string $model): bool => Gate::allows('viewAny', $model);

        return $transactions->each(function (AccountTransaction $t) use ($expenses, $payments, $payouts, $vouchers, $transfers, $canOpen): void {
            $flow = ['title' => $t->description, 'party' => null, 'url' => null];

            switch ($t->source_type) {
                case TransactionSource::Expense:
                    $e = $expenses->get($t->source_id);
                    $flow = ['title' => 'Expense · '.($e?->category?->name ?? 'Uncategorised'), 'party' => $e?->payee,
                        'url' => $e && $canOpen(Expense::class) ? route('expenses.index', ['q' => $e->reference ?: $e->payee]) : null];
                    break;
                case TransactionSource::CurrencyConversion:
                    $p = $payments->get($t->source_id);
                    $flow = ['title' => 'Payment received (USD converted)', 'party' => $p?->company?->name,
                        'url' => $p && $canOpen(CompanyPayment::class) ? route('company-payments.show', $p) : null];
                    break;
                case TransactionSource::Commission:
                    $c = $payouts->get($t->source_id);
                    $flow = ['title' => 'Commission payout', 'party' => $c?->dispatcher?->name,
                        'url' => $c && $canOpen(\App\Models\Commission::class) ? route('commissions.ledger', $c->dispatcher_id) : null];
                    break;
                case TransactionSource::JournalVoucher:
                    $v = $vouchers->get($t->source_id);
                    $flow = ['title' => 'Journal voucher '.($v?->jv_number ?? ''), 'party' => $v ? $v->debitAccount?->name.' ← '.$v->creditAccount?->name : null,
                        'url' => $v && $canOpen(JournalVoucher::class) ? route('journal-vouchers.index') : null];
                    break;
                case TransactionSource::Transfer:
                    $x = $transfers->get($t->source_id);
                    $flow = ['title' => 'Transfer between accounts', 'party' => $x ? $x->from?->name.' → '.$x->to?->name : null,
                        'url' => $x && Gate::allows('accounts.transfer') ? route('account-transfers.index') : null];
                    break;
                default:
                    break;
            }

            $t->setAttribute('flow', $flow);
        });
    }
}
