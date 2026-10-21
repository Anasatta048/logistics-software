<?php

namespace App\Http\Controllers;

use App\Services\Support\DatabaseBackup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * For uptime monitors (UptimeRobot, Better Stack, ...): 200 when the database,
 * cache and backups are healthy, 503 with the failing part otherwise. No
 * secrets and no tenant data in the answer.
 */
final class HealthController extends Controller
{
    public function __invoke(DatabaseBackup $backup): JsonResponse
    {
        $checks = [
            'database' => $this->attempt(fn () => DB::selectOne('select 1 as ok')->ok === 1),
            'cache' => $this->attempt(function (): bool {
                Cache::put('health', 'ok', 10);

                return Cache::get('health') === 'ok';
            }),
            'storage' => $this->attempt(fn () => is_writable(storage_path('app'))),
            'backup' => $this->attempt(function () use ($backup): bool {
                $age = $backup->latestAgeHours();

                // A fresh install has no backup yet; only a stale one is a fault.
                return $age === null ? ! app()->isProduction() : $age <= config('backup.max_age_hours');
            }),
        ];

        $healthy = ! in_array(false, $checks, true);

        return response()->json(['status' => $healthy ? 'ok' : 'degraded', 'checks' => $checks, 'time' => now()->toIso8601String()], $healthy ? 200 : 503);
    }

    private function attempt(callable $check): bool
    {
        try {
            return (bool) $check();
        } catch (Throwable) {
            return false;
        }
    }
}
