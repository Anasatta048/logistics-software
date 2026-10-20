<?php

namespace App\Services\Support;

use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Gap-free per-workspace counters. The increment happens under a row lock, so
 * two dispatchers saving at the same instant can never be handed one number.
 * Call it inside the same transaction that saves the document, so a failed
 * save does not burn a number.
 */
final class NumberSequence
{
    /**
     * @param  int  $start  the first number a brand-new counter hands out
     */
    public static function next(string $key, int $start = 1): int
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            throw new LogicException('Numbers are per workspace; no workspace is active.');
        }

        return DB::transaction(function () use ($tenantId, $key, $start): int {
            $row = DB::table('number_sequences')->where('tenant_id', $tenantId)->where('key', $key)->lockForUpdate()->first();

            if ($row === null) {
                // insertOrIgnore: a concurrent first call may have created it; re-read under the lock.
                DB::table('number_sequences')->insertOrIgnore([
                    'tenant_id' => $tenantId, 'key' => $key, 'last_value' => max(0, $start - 1),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $row = DB::table('number_sequences')->where('tenant_id', $tenantId)->where('key', $key)->lockForUpdate()->first();
            }

            $next = (int) $row->last_value + 1;
            DB::table('number_sequences')->where('id', $row->id)->update(['last_value' => $next, 'updated_at' => now()]);

            return $next;
        });
    }

    /** "PREFIX-0001" with the tenant's chosen prefix and width. */
    public static function format(string $prefix, int $number, int $padding): string
    {
        $prefix = trim($prefix);

        return ($prefix === '' ? '' : $prefix.'-').str_pad((string) $number, max(1, $padding), '0', STR_PAD_LEFT);
    }
}
