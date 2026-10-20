<?php

namespace App\Services\Commission;

use App\Models\Commission;
use App\Models\CommissionTier;
use App\Models\Load;
use App\Support\Settings\TenantSettings;
use App\Support\Timezones;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Slab commission. A dispatcher's margin for the month climbs a ladder; each
 * slice of it earns the rate of the slab it falls in (like tax brackets, so a
 * big month never re-prices the loads already paid at a lower slab).
 *
 *   slabs: 0 -> 10 %, 5 000 -> 12 %, 10 000 -> 15 %
 *   $4 000 already this month, a load with $2 000 margin:
 *     1 000 x 10 % + 1 000 x 12 % = $220  (an effective 11 %)
 */
final class TieredCommission
{
    /** @return Collection<int, CommissionTier> */
    public function tiers(): Collection
    {
        return CommissionTier::query()->orderBy('from_amount')->get();
    }

    public function enabled(): bool
    {
        return TenantSettings::get('finance.commission_mode') === 'tiered' && $this->tiers()->isNotEmpty();
    }

    /**
     * @return array{amount: float, effective_rate: float}
     */
    public function amountFor(int $dispatcherId, Load $load, float $base): array
    {
        $tiers = $this->tiers();
        $monthStart = Carbon::parse(Timezones::today())->startOfMonth();

        // Margin already booked this month, leaving out this load so a recalculation is stable.
        $before = (float) Commission::query()
            ->where('dispatcher_id', $dispatcherId)
            ->where('load_id', '!=', $load->id)
            ->where('status', '!=', 'reversed')
            ->where('calculated_at', '>=', $monthStart)
            ->sum('margin_amount');

        $from = $before;
        $to = $before + max(0.0, $base);
        $amount = 0.0;

        foreach ($tiers->values() as $i => $tier) {
            $lower = (float) $tier->from_amount;
            $upper = $tiers->values()[$i + 1]->from_amount ?? null;
            $upper = $upper === null ? INF : (float) $upper;

            $slice = max(0.0, min($to, $upper) - max($from, $lower));
            $amount += $slice * (float) $tier->rate / 100;
        }

        return [
            'amount' => round($amount, 2),
            'effective_rate' => $base > 0 ? round($amount / $base * 100, 2) : 0.0,
        ];
    }
}
