<?php

namespace App\Services\Tenancy;

use App\Enums\RequestStatus;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Mail\TenantWelcomeMail;
use App\Models\EquipmentType;
use App\Models\Plan;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Models\TenantRequest;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Turns an approved request (or a hand-entered company) into a working
 * workspace: the tenant row, its first admin, starter data and a welcome email
 * carrying a set-your-password link (never a plaintext password).
 */
final class TenantProvisioner
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    /**
     * @param  array{name: string, contact_name: string, email: string, phone?: ?string}  $data
     * @return array{tenant: Tenant, admin: User, setup_url: string}
     */
    public function provision(array $data, Plan $plan, string $billingCycle = 'monthly', ?TenantRequest $request = null): array
    {
        if (User::withoutGlobalScopes()->where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages(['email' => 'A user with this email already exists on the platform.']);
        }

        /** @var array{tenant: Tenant, admin: User} $made */
        $made = DB::transaction(function () use ($data, $plan, $billingCycle, $request): array {
            $tenant = Tenant::create($this->tenantAttributes($data, $plan, $billingCycle));

            $admin = $this->context->run($tenant, function () use ($data): User {
                $this->seedStarterData();

                $admin = new User([
                    'name' => $data['contact_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password' => Str::random(48),
                    'status' => UserStatus::Active,
                ]);
                $admin->email_verified_at = now();
                $admin->save();
                $admin->assignRole('admin');

                return $admin;
            });

            $request?->update([
                'status' => RequestStatus::Approved,
                'tenant_id' => $tenant->id,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            PlatformAuditLog::record('tenant.provisioned', $tenant->id, "Workspace {$tenant->name} created on {$plan->name}", [
                'request_id' => $request?->id,
            ]);

            return ['tenant' => $tenant, 'admin' => $admin];
        });

        $setupUrl = $this->setupUrl($made['admin']);
        Mail::to($made['admin']->email)->queue(new TenantWelcomeMail($made['tenant'], $made['admin'], $setupUrl));

        return $made + ['setup_url' => $setupUrl];
    }

    /** @return array<string, mixed> */
    private function tenantAttributes(array $data, Plan $plan, string $billingCycle): array
    {
        $onTrial = $plan->trial_days > 0;

        return [
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'plan_id' => $plan->id,
            'status' => $onTrial ? TenantStatus::Trial : TenantStatus::Active,
            'billing_cycle' => $billingCycle,
            'trial_ends_at' => $onTrial ? now()->addDays($plan->trial_days) : null,
            'subscription_ends_at' => $onTrial ? null : $this->periodEnd(now(), $billingCycle),
            'contact_name' => $data['contact_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ];
    }

    public function periodEnd(\Carbon\CarbonInterface $from, string $billingCycle): \Carbon\CarbonInterface
    {
        return $billingCycle === 'yearly' ? $from->copy()->addYear() : $from->copy()->addMonth();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;

        for ($i = 2; Tenant::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /** Runs inside the new tenant's context, so rows are stamped with its id. */
    private function seedStarterData(): void
    {
        foreach (config('saas.starter_equipment_types') as $name) {
            EquipmentType::create(['name' => $name]);
        }
    }

    private function setupUrl(User $admin): string
    {
        $token = Password::broker()->createToken($admin);

        return route('password.reset', ['token' => $token, 'email' => $admin->email]);
    }
}
