<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CommissionTier;
use App\Services\Commission\TieredCommission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Settings -> Commission slabs: the ladder used when commission is set to "slabs". */
final class CommissionTierController extends Controller
{
    private const MAX_TIERS = 8;

    public function edit(TieredCommission $tiers): View
    {
        return view('settings.commission-tiers', ['tiers' => $tiers->tiers(), 'active' => $tiers->enabled()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rows = collect($request->validate([
            'tiers' => ['nullable', 'array', 'max:'.self::MAX_TIERS],
            'tiers.*.from' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'tiers.*.rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ])['tiers'] ?? [])->map(fn (array $t): array => ['from' => round((float) $t['from'], 2), 'rate' => round((float) $t['rate'], 2)])
            ->sortBy('from')->values();

        if ($rows->isNotEmpty() && (float) $rows->first()['from'] !== 0.0) {
            throw ValidationException::withMessages(['tiers' => 'The first slab must start at 0.']);
        }

        if ($rows->pluck('from')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['tiers' => 'Two slabs start at the same amount.']);
        }

        DB::transaction(function () use ($rows): void {
            CommissionTier::query()->delete();
            $rows->each(fn (array $t) => CommissionTier::create(['from_amount' => $t['from'], 'rate' => $t['rate']]));
        });

        return back()->with('success', 'Commission slabs saved.');
    }
}
