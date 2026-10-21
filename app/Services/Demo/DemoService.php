<?php

namespace App\Services\Demo;

use App\Enums\TenantStatus;
use App\Models\LoadAttachment;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PlanModules;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * The public live demo: each visitor gets a private sandbox workspace filled with
 * sample data, signed in for one hour, then wiped. Nothing is shared between
 * visitors, nothing outside the sandbox can be reached, and the number of
 * sandboxes one address (or the whole site) may start is capped.
 */
final class DemoService
{
    /** Signed "form rendered at" stamp: refuses a script that posts the button instantly. */
    public static function formToken(): string
    {
        return Crypt::encryptString((string) now()->getTimestamp());
    }

    public static function tokenAgeSeconds(?string $token): ?int
    {
        try {
            return $token ? now()->getTimestamp() - (int) Crypt::decryptString($token) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function ipHash(string $ip): string
    {
        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }

    /**
     * Create the sandbox and return its sample administrator, ready to sign in.
     *
     * @throws DemoLimitException when this visitor or the site is over its limit
     */
    public function start(string $ip, ?string $userAgent): User
    {
        $hash = self::ipHash($ip);

        // One start at a time per address, so two quick clicks cannot slip past the count.
        $lock = Cache::lock("demo-start:{$hash}", 120);

        if (! $lock->get()) {
            throw new DemoLimitException('Your demo is already being prepared. Please wait a moment.');
        }

        try {
            $this->assertAllowed($hash);

            $tag = Str::lower(Str::random(8));
            $tenant = Tenant::create([
                'name' => 'Demo workspace',
                'slug' => "demo-{$tag}",
                'plan_id' => null, // seeded without caps, capped once the sample data exists
                'status' => TenantStatus::Trial,
                'billing_cycle' => 'monthly',
                'trial_ends_at' => now()->addMinutes((int) config('demo.minutes')),
                'contact_name' => 'Demo visitor',
            ]);
            $tenant->forceFill(['is_demo' => true])->save();

            try {
                $admin = DemoDataSeeder::seedFor($tenant, "{$tag}.demo.invalid");
            } catch (Throwable $e) {
                $this->destroy($tenant);
                report($e);

                throw new DemoLimitException('The demo could not be prepared. Please try again in a minute.');
            }

            // Nobody can sign in to a sandbox user by password; entry is only through the demo button.
            User::query()->where('tenant_id', $tenant->id)->get()
                ->each(fn (User $user) => $user->forceFill(['password' => Hash::make(Str::random(48))])->save());

            $tenant->update(['plan_id' => $this->plan()->id]);

            DB::table('demo_sessions')->insert([
                'tenant_id' => $tenant->id,
                'ip_hash' => $hash,
                'user_agent' => Str::limit((string) $userAgent, 250, ''),
                'started_at' => now(),
                'expires_at' => $tenant->trial_ends_at,
            ]);

            return $admin;
        } finally {
            $lock->release();
        }
    }

    private function assertAllowed(string $hash): void
    {
        $perIp = DB::table('demo_sessions')->where('ip_hash', $hash)->where('started_at', '>=', now()->subDay())->count();

        if ($perIp >= (int) config('demo.per_ip_per_day')) {
            throw new DemoLimitException('You have used today\'s demo allowance from this connection. Request access and we will set you up properly.');
        }

        $busy = DB::table('demo_sessions')->where('expires_at', '>', now())->whereNull('ended_at')->count() >= (int) config('demo.max_active')
            || DB::table('demo_sessions')->where('started_at', '>=', now()->subHour())->count() >= (int) config('demo.max_per_hour');

        if ($busy) {
            throw new DemoLimitException('The demo is very busy right now. Please try again in a few minutes.');
        }
    }

    /** The hidden plan sandboxes run on: small caps, no import/export. */
    public function plan(): Plan
    {
        return Plan::query()->updateOrCreate(['slug' => 'demo'], [
            'name' => 'Demo', 'tagline' => 'Sandbox', 'monthly_price' => 0, 'yearly_price' => 0, 'trial_days' => 0,
            'features' => [], 'is_featured' => false, 'is_active' => false, 'sort_order' => 99,
            'modules' => array_values(array_diff(PlanModules::keys(), ['data'])),
        ] + config('demo.plan'));
    }

    public function attachmentCapReached(Tenant $tenant): bool
    {
        return app(TenantContext::class)->run($tenant, fn (): bool => LoadAttachment::query()->count() >= (int) config('demo.max_attachments'));
    }

    /** Wipe every sandbox whose hour is over (or all of them). Returns how many were removed. */
    public function purge(bool $all = false): int
    {
        $count = 0;

        Tenant::query()->where('is_demo', true)
            ->when(! $all, fn ($q) => $q->where('trial_ends_at', '<', now()->subMinutes(2)))
            ->each(function (Tenant $tenant) use (&$count): void {
                $this->destroy($tenant);
                $count++;
            });

        // The counters only matter for 24 hours; keep a week for forensics, then forget.
        DB::table('demo_sessions')->where('started_at', '<', now()->subDays(7))->delete();

        return $count;
    }

    /** Delete a sandbox and everything that belongs to it, files included. */
    public function destroy(Tenant $tenant): void
    {
        abort_unless($tenant->is_demo, 500, 'Refusing to purge a real workspace.');

        $id = $tenant->id;
        $tables = collect(DB::select(
            'select distinct table_name as n from information_schema.columns where table_schema = ? and column_name = ?',
            [DB::getDatabaseName(), 'tenant_id'],
        ))->pluck('n')->reject(fn (string $name): bool => in_array($name, ['tenants', 'demo_sessions'], true));

        $this->deleteFiles($tables, $id);

        $userIds = DB::table('users')->where('tenant_id', $id)->pluck('id');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Rows that reach the tenant only through a parent.
            DB::table('commission_payout_allocations')->whereIn('commission_id', DB::table('commissions')->where('tenant_id', $id)->select('id'))->delete();
            DB::table('company_payment_loads')->whereIn('company_payment_id', DB::table('company_payments')->where('tenant_id', $id)->select('id'))->delete();
            DB::table('company_user')->whereIn('company_id', DB::table('companies')->where('tenant_id', $id)->select('id'))->delete();
            DB::table('role_has_permissions')->whereIn('role_id', DB::table('roles')->where('team_id', $id)->select('id'))->delete();
            DB::table('model_has_roles')->where('team_id', $id)->delete();
            DB::table('model_has_permissions')->where('team_id', $id)->delete();
            DB::table('roles')->where('team_id', $id)->delete();
            DB::table('notifications')->where('notifiable_type', User::class)->whereIn('notifiable_id', $userIds)->delete();
            DB::table('sessions')->whereIn('user_id', $userIds)->delete();

            foreach ($tables as $table) {
                DB::table($table)->where('tenant_id', $id)->delete();
            }

            DB::table('demo_sessions')->where('tenant_id', $id)->update(['ended_at' => now()]);
            DB::table('tenants')->where('id', $id)->delete();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        Cache::forget("tenant.{$id}");
    }

    /** Uploaded documents of the sandbox (any "*_path" column of a tenant table). */
    private function deleteFiles($tables, int $tenantId): void
    {
        $columns = DB::select(
            'select table_name as t, column_name as c from information_schema.columns where table_schema = ? and column_name like ?',
            [DB::getDatabaseName(), '%\\_path'],
        );

        foreach ($columns as $column) {
            if (! $tables->contains($column->t)) {
                continue;
            }

            foreach (DB::table($column->t)->where('tenant_id', $tenantId)->whereNotNull($column->c)->pluck($column->c) as $path) {
                foreach (['public', 'local'] as $disk) {
                    Storage::disk($disk)->delete($path);
                }
            }
        }
    }
}
