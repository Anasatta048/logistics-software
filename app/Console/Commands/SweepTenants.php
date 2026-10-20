<?php

namespace App\Console\Commands;

use App\Enums\TenantStatus;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Nightly: lock workspaces whose trial or paid period (plus grace) has ended.
 * Access is also checked live from the dates (Tenant::isUsable), so this only
 * keeps the stored status honest for the super admin's lists. One UPDATE per
 * case, no per-tenant loop, so it stays cheap with thousands of tenants.
 */
final class SweepTenants extends Command
{
    protected $signature = 'tenants:sweep';

    protected $description = 'Suspend tenants whose trial or subscription has ended';

    public function handle(): int
    {
        $now = now();

        $trials = Tenant::query()
            ->where('status', TenantStatus::Trial)
            ->where('trial_ends_at', '<', $now)
            ->update(['status' => TenantStatus::Suspended, 'suspended_at' => $now, 'suspended_reason' => 'Your free trial has ended.']);

        $paid = Tenant::query()
            ->where('status', TenantStatus::Active)
            ->where('subscription_ends_at', '<', $now->copy()->subDays(Tenant::GRACE_DAYS))
            ->update(['status' => TenantStatus::Suspended, 'suspended_at' => $now, 'suspended_reason' => 'Your subscription has expired.']);

        if ($trials + $paid > 0) {
            PlatformAuditLog::record('tenants.swept', null, "Suspended {$trials} expired trial(s) and {$paid} lapsed subscription(s)");
        }

        $this->info("Suspended {$trials} trial(s), {$paid} subscription(s).");

        return self::SUCCESS;
    }
}
