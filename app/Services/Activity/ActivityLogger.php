<?php

namespace App\Services\Activity;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Writes one readable row to the activity log. Logging must never break the
 * action being logged, so a failure here is reported and swallowed.
 */
final class ActivityLogger
{
    /**
     * @param  list<array{field: string, old: ?string, new: ?string}>|null  $changes
     */
    public function log(
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        ?string $subjectLabel = null,
        ?array $changes = null,
        ?User $actor = null,
    ): void {
        $actor ??= Auth::user();

        // Seeders, artisan and queue work have no person behind them.
        if ($actor === null && app()->runningInConsole()) {
            return;
        }

        try {
            ActivityLog::create([
                'user_id' => $actor?->getKey(),
                'user_name' => $actor?->name,
                'module' => $module,
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subjectLabel,
                'description' => $description,
                'changes' => $changes ?: null,
                'ip_address' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 255) ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
