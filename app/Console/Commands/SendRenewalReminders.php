<?php

namespace App\Console\Commands;

use App\Enums\TenantStatus;
use App\Mail\RenewalReminderMail;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Daily: warn a workspace 7, 3 and 1 day before its trial or paid period ends,
 * and once more when it has ended (inside the grace days). `reminder_stage` +
 * `reminder_for` remember what was sent for the current period, so a re-run
 * never mails twice and a renewal starts the sequence afresh.
 */
final class SendRenewalReminders extends Command
{
    /** Days-left thresholds, earliest first. */
    public const STAGES = [7, 3, 1, 0];

    protected $signature = 'tenants:remind';

    protected $description = 'Email workspaces whose trial or subscription is about to end';

    public function handle(): int
    {
        $sent = 0;
        $horizon = now()->addDays(self::STAGES[0] + 1);
        $floor = now()->subDays(Tenant::GRACE_DAYS);

        Tenant::query()
            ->whereIn('status', [TenantStatus::Trial, TenantStatus::Active])
            ->whereNotNull('email')
            ->where(fn ($q) => $q
                ->where(fn ($t) => $t->where('status', TenantStatus::Trial)->whereBetween('trial_ends_at', [$floor, $horizon]))
                ->orWhere(fn ($t) => $t->where('status', TenantStatus::Active)->whereBetween('subscription_ends_at', [$floor, $horizon])))
            ->each(function (Tenant $tenant) use (&$sent): void {
                $ends = $tenant->endsAt();

                if ($ends === null) {
                    return;
                }

                // Whole days left, rounded up: "ends tomorrow evening" is 1, not 0.
                $left = (int) max(0, ceil(now()->diffInHours($ends, false) / 24));
                $stage = $ends->isPast() ? 0 : collect(self::STAGES)->filter(fn (int $s): bool => $left <= $s)->min();

                $already = $tenant->reminder_for?->toDateString() === $ends->toDateString() ? $tenant->reminder_stage : null;

                // Stages count down, so only a lower one than already sent is new.
                if ($stage === null || ($already !== null && $stage >= $already)) {
                    return;
                }

                Mail::to($tenant->email)->queue(new RenewalReminderMail($tenant, $stage === 0 ? 0 : $left, $tenant->status === TenantStatus::Trial));
                $tenant->forceFill(['reminder_stage' => $stage, 'reminder_for' => $ends->toDateString()])->saveQuietly();
                $sent++;
            });

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
