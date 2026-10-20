<?php

namespace App\Services\Ledger;

use App\Enums\LedgerEntryType;
use App\Models\Company;
use App\Models\LedgerEntry;
use App\Models\Load;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The only writer of party_ledger_entries (same idea as AccountService for
 * account balances): every other module records what happened to a company's or
 * dispatcher's balance by calling post(), and reads the position back from here.
 *
 * Balance means "what the party still has to settle". Partial payments simply
 * leave a balance; an overpayment pushes it below zero, which is an advance —
 * and because the next accrual is added to that same number, an advance is used
 * up automatically by the next commission with no separate bookkeeping.
 */
final class LedgerService
{
    /**
     * @param  Model  $party  A Company or a dispatcher User.
     * @param  float  $amount  Signed effect on the balance; zero writes nothing.
     */
    public function post(
        Model $party,
        LedgerEntryType $type,
        float $amount,
        string $description,
        ?Model $source = null,
        ?Load $load = null,
        ?User $actor = null,
        ?string $occurredOn = null,
    ): ?LedgerEntry {
        $amount = round($amount, 2);

        if ($amount === 0.0) {
            return null;
        }

        $partyType = $this->partyType($party);

        return DB::transaction(function () use ($party, $partyType, $type, $amount, $description, $source, $load, $actor, $occurredOn): LedgerEntry {
            // Serialises concurrent postings for this party so two of them can
            // never read the same previous balance.
            $party::query()->whereKey($party->getKey())->lockForUpdate()->firstOrFail();

            $balance = round($this->balance($party) + $amount, 2);

            // `companies.advance_balance` predates the ledger and is still read by
            // older screens; it is kept equal to the credit side of the ledger.
            if ($party instanceof Company) {
                Company::query()->whereKey($party->getKey())->update(['advance_balance' => round(max(0, -$balance), 2)]);
            }

            return LedgerEntry::create([
                'party_type' => $partyType,
                'party_id' => $party->getKey(),
                'entry_type' => $type,
                'amount' => $amount,
                'balance_after' => $balance,
                'load_id' => $load?->id,
                'source_type' => $source === null ? null : class_basename($source),
                'source_id' => $source?->getKey(),
                'description' => $description,
                'occurred_on' => $occurredOn ?? now()->toDateString(),
                'created_by' => $actor?->id,
            ]);
        });
    }

    public function balance(Model $party): float
    {
        return (float) (LedgerEntry::query()
            ->where('party_type', $this->partyType($party))
            ->where('party_id', $party->getKey())
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);
    }

    /**
     * What has been accrued against one load, net of reversals. Used to reverse
     * exactly what was booked, whatever the load row says by then.
     */
    public function accruedForLoad(Model $party, Load $load): float
    {
        return round((float) LedgerEntry::query()
            ->where('party_type', $this->partyType($party))
            ->where('party_id', $party->getKey())
            ->where('load_id', $load->id)
            ->sum('amount'), 2);
    }

    /**
     * Where the party stands right now: each open item with how much of it has
     * been covered, the advance (if any), and the headline figures.
     *
     * Money is matched to accruals oldest first. That is derived on read from
     * the ledger, never stored, so a late reversal or a correction simply
     * re-flows the same money onto the next item and nothing can drift.
     *
     * @return array{items: Collection<int, object>, balance: float, outstanding: float, advance: float, accrued: float, settled: float}
     */
    public function position(Model $party): array
    {
        $entries = LedgerEntry::query()
            ->where('party_type', $this->partyType($party))
            ->where('party_id', $party->getKey())
            ->orderBy('id')
            ->get(['id', 'entry_type', 'amount', 'load_id', 'occurred_on']);

        $perLoad = [];
        $pool = 0.0;
        $accrued = 0.0;

        foreach ($entries as $entry) {
            $amount = (float) $entry->amount;

            if ($entry->load_id !== null) {
                $perLoad[$entry->load_id] ??= ['amount' => 0.0, 'first' => $entry->id, 'date' => $entry->occurred_on->toDateString()];
                $perLoad[$entry->load_id]['amount'] += $amount;
            } else {
                // Payments and payouts are negative, their corrections positive.
                $pool -= $amount;
            }

            if ($entry->entry_type === LedgerEntryType::CommissionAccrued) {
                $accrued += $amount;
            }
        }

        $references = Load::query()->whereIn('id', array_keys($perLoad))->pluck('reference_number', 'id');
        $items = collect($perLoad)
            ->filter(fn (array $row): bool => round($row['amount'], 2) > 0)
            ->sortBy('first')
            ->map(fn (array $row, int $loadId): object => (object) [
                'load_id' => $loadId,
                'label' => (string) ($references[$loadId] ?? 'Load #'.$loadId),
                'occurred_on' => $row['date'],
                'amount' => round($row['amount'], 2),
                'applied' => 0.0,
                'remaining' => round($row['amount'], 2),
            ])
            ->values();

        // More charged than ever settled outside of loads: a standalone debit.
        if ($pool < 0) {
            $items->push((object) [
                'load_id' => null, 'label' => 'Other charges', 'occurred_on' => now()->toDateString(),
                'amount' => round(-$pool, 2), 'applied' => 0.0, 'remaining' => round(-$pool, 2),
            ]);
            $pool = 0.0;
        }

        foreach ($items as $item) {
            $take = min($pool, $item->remaining);
            $item->applied = round($take, 2);
            $item->remaining = round($item->remaining - $take, 2);
            $pool = round($pool - $take, 2);
        }

        $balance = round($entries->sum(fn (LedgerEntry $e): float => (float) $e->amount), 2);

        return [
            'items' => $items,
            'balance' => $balance,
            'outstanding' => round(max(0, $balance), 2),
            'advance' => round(max(0, -$balance), 2),
            'accrued' => round($accrued, 2),
            'settled' => round($items->sum('applied') + $pool, 2),
        ];
    }

    /**
     * @return array{opening: float, closing: float, entries: Collection<int, LedgerEntry>}
     */
    public function statement(Model $party, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $base = LedgerEntry::query()
            ->where('party_type', $this->partyType($party))
            ->where('party_id', $party->getKey());

        $opening = $from === null ? 0.0 : (float) ((clone $base)
            ->where('occurred_on', '<', $from->toDateString())
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);

        $entries = (clone $base)
            ->with(['creator:id,name', 'loadRecord:id,reference_number'])
            ->when($from, fn ($q) => $q->where('occurred_on', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->where('occurred_on', '<=', $to->toDateString()))
            ->orderBy('id')
            ->get();

        return [
            'opening' => $opening,
            'closing' => (float) ($entries->last()?->balance_after ?? $opening),
            'entries' => $entries,
        ];
    }

    public function partyType(Model $party): string
    {
        return match (true) {
            $party instanceof Company => LedgerEntry::COMPANY,
            $party instanceof User => LedgerEntry::DISPATCHER,
            default => throw new InvalidArgumentException('Only companies and dispatchers have a ledger.'),
        };
    }
}
