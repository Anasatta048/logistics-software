<?php

namespace App\Services\Account;

use App\Enums\TransactionSource;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\AccountTransfer;
use Illuminate\Support\Collection;

/**
 * The two money-trail views built on the account ledger:
 *  - statement(): one account, opening -> every line with running balance -> closing
 *  - fundsFlow(): every account for a window, opening + in - out = closing,
 *    broken down by what caused the movement
 *
 * Both work off `occurred_on` (the business date), not the day a row was keyed.
 * In this ledger an account is an asset: a Debit is money in, a Credit money out.
 */
final class MoneyFlowService
{
    private const SIGNED = "CASE WHEN type = 'debit' THEN amount ELSE -amount END";

    public function __construct(private readonly FlowLabeler $labels)
    {
    }

    /**
     * @return array{opening: float, in: float, out: float, closing: float, rows: Collection<int, AccountTransaction>, from: ?string, to: ?string}
     */
    public function statement(Account $account, ?string $from, ?string $to): array
    {
        $opening = (float) $account->opening_balance;

        if ($from) {
            $opening += (float) AccountTransaction::query()
                ->where('account_id', $account->id)
                ->where('occurred_on', '<', $from)
                ->selectRaw('COALESCE(SUM('.self::SIGNED.'), 0) as net')
                ->toBase()->value('net');
        }

        $rows = AccountTransaction::query()
            ->where('account_id', $account->id)
            ->when($from, fn ($q) => $q->where('occurred_on', '>=', $from))
            ->when($to, fn ($q) => $q->where('occurred_on', '<=', $to))
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->limit(2000)
            ->get();

        $running = $opening;
        $in = $out = 0.0;

        foreach ($rows as $row) {
            $amount = (float) $row->amount;
            $row->type->sign() > 0 ? $in += $amount : $out += $amount;
            $running += $row->type->sign() * $amount;
            $row->setAttribute('balance_after', round($running, 2));
        }

        $this->labels->annotate($rows);

        return [
            'opening' => round($opening, 2),
            'in' => round($in, 2),
            'out' => round($out, 2),
            'closing' => round($opening + $in - $out, 2),
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @param  array{from?: ?string, to?: ?string, account_id?: int|string|null}  $filters
     * @return array{
     *     accounts: list<array{id: int, name: string, type: string, opening: float, in: float, out: float, closing: float}>,
     *     totals: array{opening: float, in: float, out: float, closing: float, external_in: float, external_out: float},
     *     by_source: list<array{source: string, label: string, in: float, out: float}>,
     *     rows: Collection<int, AccountTransaction>,
     *     truncated: bool
     * }
     */
    public function fundsFlow(array $filters): array
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $accountId = $filters['account_id'] ?? null;

        $accounts = Account::query()
            ->when($accountId, fn ($q) => $q->whereKey($accountId))
            ->orderBy('name')->get(['id', 'name', 'type', 'opening_balance']);

        $before = $from
            ? AccountTransaction::query()->where('occurred_on', '<', $from)
                ->selectRaw('account_id, COALESCE(SUM('.self::SIGNED.'), 0) as net')
                ->groupBy('account_id')->toBase()->get()->pluck('net', 'account_id')
            : collect();

        $window = fn () => AccountTransaction::query()
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId))
            ->when($from, fn ($q) => $q->where('occurred_on', '>=', $from))
            ->when($to, fn ($q) => $q->where('occurred_on', '<=', $to));

        $moves = $window()
            ->selectRaw("account_id, type, source_type, COALESCE(SUM(amount), 0) as total")
            ->groupBy('account_id', 'type', 'source_type')
            ->toBase()->get();

        $perAccount = $moves->groupBy('account_id');
        $rows = [];

        foreach ($accounts as $account) {
            $mine = $perAccount->get($account->id, collect());
            $in = (float) $mine->where('type', 'debit')->sum('total');
            $out = (float) $mine->where('type', 'credit')->sum('total');
            $opening = (float) $account->opening_balance + (float) ($before[$account->id] ?? 0);

            $rows[] = [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type->label(),
                'opening' => round($opening, 2),
                'in' => round($in, 2),
                'out' => round($out, 2),
                'closing' => round($opening + $in - $out, 2),
            ];
        }

        $bySource = [];
        foreach (TransactionSource::cases() as $source) {
            $of = $moves->where('source_type', $source->value);
            $bySource[] = [
                'source' => $source->value,
                'label' => self::sourceLabel($source),
                'in' => round((float) $of->where('type', 'debit')->sum('total'), 2),
                'out' => round((float) $of->where('type', 'credit')->sum('total'), 2),
            ];
        }
        $bySource = array_values(array_filter($bySource, fn (array $r): bool => $r['in'] > 0 || $r['out'] > 0));

        $detail = $window()->orderByDesc('occurred_on')->orderByDesc('id')->with('account:id,name')->limit(301)->get();
        $truncated = $detail->count() > 300;
        $detail = $this->labels->annotate($detail->take(300));

        $transfer = collect($bySource)->firstWhere('source', 'transfer');

        // Bank charges on transfers are real money gone, so they count as spending.
        $charges = (float) AccountTransfer::query()
            ->whereNull('reversed_at')
            ->when($accountId, fn ($q) => $q->where('from_account_id', $accountId))
            ->when($from, fn ($q) => $q->where('transfer_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('transfer_date', '<=', $to))
            ->sum('fee');

        return [
            'accounts' => $rows,
            'totals' => [
                'opening' => round(array_sum(array_column($rows, 'opening')), 2),
                'in' => round(array_sum(array_column($rows, 'in')), 2),
                'out' => round(array_sum(array_column($rows, 'out')), 2),
                'closing' => round(array_sum(array_column($rows, 'closing')), 2),
                // Moving money between own accounts is not income or spending.
                'external_in' => round(array_sum(array_column($rows, 'in')) - (float) ($transfer['in'] ?? 0), 2),
                'external_out' => round(array_sum(array_column($rows, 'out')) - (float) ($transfer['out'] ?? 0) + $charges, 2),
            ],
            'by_source' => $bySource,
            'rows' => $detail,
            'truncated' => $truncated,
        ];
    }

    public static function sourceLabel(TransactionSource $source): string
    {
        return match ($source) {
            TransactionSource::CurrencyConversion => 'Payments received (converted)',
            TransactionSource::Expense => 'Expenses',
            TransactionSource::Commission => 'Commission payouts',
            TransactionSource::JournalVoucher => 'Journal vouchers',
            TransactionSource::Transfer => 'Transfers between accounts',
            TransactionSource::Manual => 'Manual entries',
        };
    }
}
