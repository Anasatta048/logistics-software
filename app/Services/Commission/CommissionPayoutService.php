<?php

namespace App\Services\Commission;

use App\Enums\LedgerEntryType;
use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Commission;
use App\Models\CommissionPayout;
use App\Models\User;
use App\Services\Account\AccountService;
use App\Services\Ledger\LedgerService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pays a dispatcher in USD terms and hands it over in PKR. The amount is the
 * payer's choice — a part payment, the full balance or an advance — because the
 * dispatcher's ledger, not a single commission row, is what a payout settles.
 *
 * The PKR leaves the paying account through AccountService (the one choke point
 * for balances) inside the same transaction as the ledger line, so the two can
 * never disagree about whether the money moved.
 */
final readonly class CommissionPayoutService
{
    public function __construct(
        private LedgerService $ledger,
        private AccountService $accounts,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $dispatcher, array $data, User $actor): CommissionPayout
    {
        $usd = round((float) $data['usd_amount'], 2);
        $rate = round((float) $data['conversion_rate'], 4);
        $pkr = round($usd * $rate, 2);

        return DB::transaction(function () use ($dispatcher, $data, $actor, $usd, $rate, $pkr): CommissionPayout {
            // Lock first: the allocation below reads the dispatcher's open
            // commissions, and two simultaneous payouts must not both use them.
            User::query()->whereKey($dispatcher->id)->lockForUpdate()->firstOrFail();

            $account = filled($data['payment_account_id'] ?? null)
                ? Account::query()->findOrFail((int) $data['payment_account_id'])
                : null;
            $position = $this->ledger->position($dispatcher);

            $payout = CommissionPayout::create([
                'dispatcher_id' => $dispatcher->id,
                'usd_amount' => $usd,
                'conversion_rate' => $rate,
                'pkr_amount' => $pkr,
                'payment_account_id' => $account?->id,
                'payment_method' => $data['payment_method'] ?? null,
                'paid_at' => $data['paid_at'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'paid_by' => $actor->id,
            ]);

            $applied = $this->allocate($payout, $position['items'], $usd);
            $owed = $position['outstanding'];
            $advance = round(max(0, $usd - max($owed, $applied)), 2);

            $this->ledger->post(
                $dispatcher,
                LedgerEntryType::PayoutMade,
                -$usd,
                $this->describe($payout, $usd, $pkr, $rate, $owed, $advance),
                source: $payout,
                actor: $actor,
                occurredOn: Carbon::parse($data['paid_at'])->toDateString(),
            );

            // In this ledger money leaving an account is a credit.
            if ($account !== null) {
                $this->accounts->recordTransaction(
                    $account,
                    TransactionType::Credit,
                    $pkr,
                    TransactionSource::Commission,
                    $payout->id,
                    "Commission payout #{$payout->id} to {$dispatcher->name}: USD ".number_format($usd, 2).' @ '.number_format($rate, 2),
                    $actor,
                    Carbon::parse($data['paid_at'])->toDateString(),
                );
            }

            return $payout->refresh();
        });
    }

    /**
     * The rate the form pre-fills: the last one a payout used, else null.
     */
    public function lastRate(): ?float
    {
        $rate = CommissionPayout::query()->orderByDesc('id')->value('conversion_rate');

        return $rate === null ? null : (float) $rate;
    }

    /**
     * Records which commissions this payout covered, oldest first. Whatever no
     * open commission can absorb is left as an advance on the ledger.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $items
     */
    private function allocate(CommissionPayout $payout, $items, float $available): float
    {
        $open = $items->filter(fn (object $item): bool => $item->load_id !== null && $item->remaining > 0.005);
        $commissions = Commission::query()->whereIn('load_id', $open->pluck('load_id'))->get()->keyBy('load_id');

        $remaining = $available;
        $total = 0.0;

        foreach ($open as $item) {
            if ($remaining <= 0.005 || ! isset($commissions[$item->load_id])) {
                continue;
            }

            $amount = round(min($item->remaining, $remaining), 2);
            $payout->commissions()->attach($commissions[$item->load_id]->id, ['amount' => $amount]);
            $remaining = round($remaining - $amount, 2);
            $total = round($total + $amount, 2);
        }

        return $total;
    }

    private function describe(CommissionPayout $payout, float $usd, float $pkr, float $rate, float $owed, float $advance): string
    {
        $text = sprintf(
            'Payout #%d: $%s = Rs %s @ %s',
            $payout->id,
            number_format($usd, 2),
            number_format($pkr, 2),
            number_format($rate, 2),
        );

        return match (true) {
            $advance > 0 => $text.sprintf(' — $%s paid in advance beyond the $%s owed', number_format($advance, 2), number_format($owed, 2)),
            $usd < $owed => $text.sprintf(' — partial, $%s still owed', number_format($owed - $usd, 2)),
            default => $text.' — settled in full',
        };
    }
}
