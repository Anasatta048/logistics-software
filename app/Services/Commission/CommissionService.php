<?php

namespace App\Services\Commission;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\LedgerEntryType;
use App\Models\Commission;
use App\Models\CommissionPayout;
use App\Models\LedgerEntry;
use App\Models\CompanyPayment;
use App\Models\Load;
use App\Models\User;
use App\Services\Ledger\LedgerService;
use App\Support\Timezones;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

final class CommissionService
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    /**
     * A load marked Paid accrues the dispatcher's cut of the company
     * commission (FLOW_UPDATE_PLAN Section 4: `Company Commission Amount ×
     * Dispatcher %`). Driver pay is out of scope entirely, so the base is
     * simply what the company owes us for the load.
     *
     * Idempotent by design — the row is keyed on load_id, so replaying the
     * event (or recalculating after a rate fix) updates the same commission
     * instead of paying twice.
     */
    public function calculateForLoad(Load $load, ?User $actor = null): ?Commission
    {
        $dispatcherId = $load->dispatcher_id;

        if ($dispatcherId === null) {
            Log::warning('Load has no dispatcher, no commission accrued.', ['load_id' => $load->id]);

            return null;
        }

        $existing = Commission::where('load_id', $load->id)->first();

        // Read straight off the relation, not `$load->dispatcher`, because the
        // caller may have already cached a dispatcher from before a percentage
        // change — a recalculation has to use the percentage that is live now.
        // The user exists: dispatcher_id is a foreign key and the null case
        // above already returned.
        $dispatcher = $load->dispatcher()->firstOrFail();
        $type = $dispatcher->commission_type;
        $percentage = (float) ($dispatcher->commission_percentage ?? 0);
        $base = round((float) $load->company_commission_amount, 2);
        // A percentage cut earns nothing when the base is zero (no driver, so
        // no company, so no commission) and a fixed cut earns the same on every
        // dispatch. Either way a row is still written rather than skipped, so
        // the dispatcher report shows the load was considered.
        $amount = $type === CommissionType::Fixed
            ? max(0, (float) $dispatcher->commission_fixed_amount)
            : round(max(0, $base) * $percentage / 100, 2);

        $commission = Commission::updateOrCreate(
            ['load_id' => $load->id],
            [
                'dispatcher_id' => $dispatcherId,
                'margin_amount' => $base,
                'commission_type' => $type,
                'commission_percentage' => $percentage,
                'commission_fixed_amount' => $dispatcher->commission_fixed_amount,
                'commission_amount' => $amount,
                'status' => CommissionStatus::Accrued,
                'calculated_at' => now(),
                'reversed_at' => null,
            ],
        );

        // What the dispatcher is owed is the ledger's business: only the
        // difference from what was already booked for this load is posted, so a
        // recalculation corrects the balance instead of adding to it.
        $this->ledger->post(
            $dispatcher,
            $this->ledger->accruedForLoad($dispatcher, $load) > 0 ? LedgerEntryType::Adjustment : LedgerEntryType::CommissionAccrued,
            $amount - $this->ledger->accruedForLoad($dispatcher, $load),
            sprintf('Commission on %s: %s', $load->reference_number, $type === CommissionType::Fixed ? '$'.number_format($amount, 2).' fixed' : rtrim(rtrim((string) $percentage, '0'), '.').'% of $'.number_format($base, 2)),
            source: $commission,
            load: $load,
            actor: $actor,
            occurredOn: Timezones::today(),
        );

        return $commission;
    }

    /**
     * Default rate for the payout form: the last rate a USD receipt was
     * converted at, so the box is pre-filled with today's number instead of
     * the user hunting for it. Null until the first conversion exists.
     */
    public function latestRate(): ?float
    {
        $rate = CompanyPayment::query()
            ->whereNotNull('converted_at')
            ->orderByDesc('conversion_date')
            ->orderByDesc('id')
            ->value('conversion_rate');

        return $rate === null ? null : (float) $rate;
    }

    /**
     * SRD 6.3: a cancellation must zero a commission that was already accrued,
     * not delete it — the row is the audit trail. The amount is left intact and
     * `totalsFor()` drops it from the payable sum, so the money still adds up.
     */
    public function reverseForLoad(Load $load, ?User $actor = null): ?Commission
    {
        $commission = Commission::where('load_id', $load->id)->first();

        if ($commission === null || $commission->status === CommissionStatus::Reversed) {
            return $commission;
        }

        $commission->update([
            'status' => CommissionStatus::Reversed,
            'reversed_at' => now(),
        ]);

        // Takes back what was booked. If part of it was already paid out, the
        // dispatcher's balance simply goes below zero: visible as money to
        // recover, with the payout itself left untouched.
        $dispatcher = $commission->dispatcher;
        $booked = $dispatcher === null ? 0.0 : $this->ledger->accruedForLoad($dispatcher, $load);

        if ($dispatcher !== null && $booked > 0) {
            $this->ledger->post(
                $dispatcher,
                LedgerEntryType::CommissionReversed,
                -$booked,
                "{$load->reference_number} cancelled — commission reversed",
                source: $commission,
                load: $load,
                actor: $actor,
                occurredOn: Timezones::today(),
            );
        }

        return $commission->refresh();
    }

    /**
     * Every dispatcher the viewer may see with where their commission stands:
     * earned, paid out, still owed, or paid ahead. A dispatcher sees only
     * themselves (scoped here, not just in the policy).
     *
     * @return \Illuminate\Support\Collection<int, object{user: User, position: array<string, mixed>, paid: float}>
     */
    public function dispatcherPositions(User $viewer): \Illuminate\Support\Collection
    {
        $paid = CommissionPayout::query()
            ->groupBy('dispatcher_id')
            ->selectRaw('dispatcher_id, SUM(usd_amount) as total')
            ->pluck('total', 'dispatcher_id');

        return User::query()
            ->where(fn ($query) => $query
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'dispatcher'))
                ->orWhereIn('id', LedgerEntry::query()->where('party_type', LedgerEntry::DISPATCHER)->select('party_id')))
            ->when($viewer->isScopedToOwnRecords(), fn ($query) => $query->whereKey($viewer->id))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): object => (object) [
                'user' => $user,
                'position' => $this->ledger->position($user),
                'paid' => round((float) ($paid[$user->id] ?? 0), 2),
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Commission>
     */
    public function paginate(array $filters, User $viewer, int $perPage = 500): LengthAwarePaginator
    {
        return $this->query($filters, $viewer)
            ->latest('calculated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Accrued / reversed for whatever filter the index is showing.
     *
     * `accrued` is the money still payable. A reversal flips the row's own
     * status rather than adding a negative row, so the reversed amount was
     * never part of the accrued sum — subtracting it again would show a
     * negative balance for a dispatcher who was paid nothing. A paid row
     * leaves `accrued` the same way, so `accrued + paid` is the lifetime take.
     *
     * @param  array<string, mixed>  $filters
     * @return array{accrued: float, paid: float, reversed: float, count: int}
     */
    public function totalsFor(array $filters, User $viewer): array
    {
        // toBase() skips model hydration: an aggregate row is a plain stdClass,
        // and a hydrated model cannot be read with an array cast.
        $row = (array) $this->query($filters, $viewer)
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN commission_amount END), 0) AS accrued', [
                CommissionStatus::Accrued->value,
            ])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN commission_amount END), 0) AS paid', [
                CommissionStatus::Paid->value,
            ])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN commission_amount END), 0) AS reversed', [
                CommissionStatus::Reversed->value,
            ])
            ->selectRaw('COUNT(*) AS rows_count')
            ->toBase()
            ->first();

        return [
            'accrued' => (float) ($row['accrued'] ?? 0),
            'paid' => (float) ($row['paid'] ?? 0),
            'reversed' => (float) ($row['reversed'] ?? 0),
            'count' => (int) ($row['rows_count'] ?? 0),
        ];
    }

    /**
     * Reports entry point (plan Phase 8): one dispatcher's totals over a
     * period. A null bound means "no limit on that side".
     *
     * @return array{accrued: float, paid: float, reversed: float, count: int}
     */
    public function totalForUser(User $user, ?Carbon $from = null, ?Carbon $to = null): array
    {
        return $this->totalsFor(array_filter([
            'dispatcher_id' => $user->id,
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
        ], static fn (mixed $value): bool => $value !== null), $user);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Commission>
     */
    private function query(array $filters, User $viewer): Builder
    {
        $term = trim((string) ($filters['q'] ?? ''));
        $status = ($filters['status'] ?? '') !== '' ? CommissionStatus::tryFrom((string) $filters['status']) : null;
        $dispatcherId = $filters['dispatcher_id'] ?? null;
        $from = $this->boundary($filters['from'] ?? null);
        $to = $this->boundary($filters['to'] ?? null);

        return Commission::query()
            ->with(['loadRecord:id,reference_number', 'dispatcher:id,name'])
            ->when($term !== '', fn (Builder $query) => $query->whereHas(
                'loadRecord',
                fn (Builder $load) => $load->where('reference_number', 'like', "%{$term}%"),
            ))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($dispatcherId !== null && $dispatcherId !== '', fn (Builder $query) => $query->where('dispatcher_id', $dispatcherId))
            ->when($from, fn (Builder $query) => $query->where('calculated_at', '>=', $from->startOfDay()))
            ->when($to, fn (Builder $query) => $query->where('calculated_at', '<=', $to->endOfDay()))
            // Scoped in the query, not just the policy, so a dispatcher can
            // never read someone else's commission by hitting the index directly.
            ->when($viewer->isScopedToOwnRecords(), fn (Builder $query) => $query->where('dispatcher_id', $viewer->id));
    }

    /**
     * Garbage in the date boxes is ignored rather than thrown, matching the
     * loose filters the master-data indexes already accept.
     */
    private function boundary(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        return $value !== '' && Carbon::hasFormat($value, 'Y-m-d') ? Carbon::parse($value) : null;
    }
}
