<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Enums\AttachmentType;
use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\DriverStatus;
use App\Enums\LoadStatus;
use App\Enums\TaxType;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Account;
use App\Models\Broker;
use App\Models\Commission;
use App\Models\Company;
use App\Models\Driver;
use App\Models\EquipmentType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Load;
use App\Models\LoadAttachment;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Account\AccountService;
use App\Services\Account\JournalVoucherService;
use App\Services\Commission\CommissionPayoutService;
use App\Services\Company\CompanyPaymentService;
use App\Services\Expense\ExpenseService;
use App\Services\Ledger\LedgerService;
use App\Services\Load\LoadCancellationService;
use App\Services\Load\LoadService;
use App\Services\Load\LoadStatusService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Demo data for manual flow testing: every module gets rows, and the money
 * paths are driven through the real services (not raw inserts) so ledgers,
 * commissions, history and notifications are consistent with what the app
 * itself would have produced.
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * Logins (password for all: "password"):
 *   admin@dreamelectlogistics.com, accountant@…, viewer@…,
 *   dispatcher@…, ali.dispatcher@…, sara.dispatcher@…
 */
class DemoDataSeeder extends Seeder
{
    /** Email domain of the seeded users; a throw-away demo workspace gets its own so addresses never collide. */
    private string $domain = 'dreamelectlogistics.com';

    private const CITIES = [
        ['Dallas, TX', 32.7767, -96.7970],
        ['Chicago, IL', 41.8781, -87.6298],
        ['Atlanta, GA', 33.7490, -84.3880],
        ['Denver, CO', 39.7392, -104.9903],
        ['Phoenix, AZ', 33.4484, -112.0740],
        ['Los Angeles, CA', 34.0522, -118.2437],
        ['Miami, FL', 25.7617, -80.1918],
        ['Seattle, WA', 47.6062, -122.3321],
        ['Nashville, TN', 36.1627, -86.7816],
        ['Kansas City, MO', 39.0997, -94.5786],
    ];

    /** The demo company everything below belongs to. */
    public static function demoTenant(): Tenant
    {
        $plan = Plan::query()->where('slug', 'enterprise')->first();

        return Tenant::query()->firstOrCreate(['slug' => 'dreamelect-demo'], [
            'name' => 'Dreamelect Logistics (demo)',
            'plan_id' => $plan?->id,
            'status' => TenantStatus::Active,
            'subscription_ends_at' => now()->addYear(),
            'contact_name' => 'Admin',
            'email' => 'admin@dreamelectlogistics.com',
        ]);
    }

    /**
     * Fill an existing (empty) workspace with the same sample data, for one visitor's
     * sandbox. Returns the sample administrator.
     */
    public static function seedFor(Tenant $tenant, string $domain): User
    {
        $seeder = new self();
        $seeder->domain = $domain;

        return app(TenantContext::class)->run($tenant, function () use ($seeder, $domain): User {
            foreach (config('saas.starter_equipment_types') as $name) {
                EquipmentType::query()->firstOrCreate(['name' => $name]);
            }

            ExpenseCategory::seedStarter();
            $seeder->seedTenant();
            mt_srand(); // seedTenant() fixes the generator for repeatable demo data; free it again

            return User::query()->where('email', "admin@{$domain}")->firstOrFail();
        });
    }

    public function run(): void
    {
        $tenant = self::demoTenant();

        // Everything is created as the demo tenant, exactly as a real one would be.
        app(TenantContext::class)->run($tenant, function (): void {
            $this->seedTenant();
        });
    }

    private function seedTenant(): void
    {
        if (Company::query()->exists() || Load::query()->exists()) {
            $this->command?->warn('Demo data skipped: companies/loads already exist. Use migrate:fresh --seed first for a clean start.');

            return;
        }

        mt_srand(2026);

        $users = $this->users();
        $companies = $this->companies();
        $drivers = $this->drivers($companies);
        $brokers = $this->brokers();
        [$cash, $hbl, $meezan] = $this->accounts();

        $loads = $this->loads($users, $companies, $drivers, $brokers);
        $this->attachments($loads, $users['dispatchers'][0]);

        // A journal voucher tied to a booked load, approved now and voided
        // automatically when that load is cancelled below.
        $voidedByCancel = $this->journalVouchers($users, [$cash, $hbl, $meezan], $loads);

        $this->companyReceipts($companies, $users['admin'], $meezan, $hbl);
        $this->commissionPayouts($users, $hbl);
        $this->expenses($users['accountant'], [$cash, $hbl, $meezan]);
        $this->cancellations($loads, $users, $voidedByCancel);

        $this->command?->info('Demo data seeded. Login: admin@dreamelectlogistics.com / password');
    }

    /* ───────────────────────── master data ───────────────────────── */

    /** @return array{admin: User, accountant: User, dispatchers: list<User>} */
    private function users(): array
    {
        $make = function (string $email, string $name, string $role, array $extra = []): User {
            $user = User::query()->where('email', $email)->first()
                ?? User::factory()->create(['name' => $name, 'email' => $email, 'status' => UserStatus::Active, ...$extra]);
            $user->syncRoles([$role]);

            return $user;
        };

        $pct = ['commission_type' => CommissionType::Percentage];

        $admin = $make("admin@{$this->domain}", 'Admin', 'admin');
        $accountant = $make("accountant@{$this->domain}", 'Ahmed Accountant', 'accountant');
        $make("viewer@{$this->domain}", 'Vera Viewer', 'viewer');

        $dispatchers = [
            $make("dispatcher@{$this->domain}", 'Dispatch Demo', 'dispatcher', [...$pct, 'commission_percentage' => 10, 'phone' => '0300-1000001']),
            $make("ali.dispatcher@{$this->domain}", 'Ali Raza', 'dispatcher', [...$pct, 'commission_percentage' => 15, 'phone' => '0300-1000002']),
            $make("sara.dispatcher@{$this->domain}", 'Sara Khan', 'dispatcher', ['commission_type' => CommissionType::Fixed, 'commission_fixed_amount' => 40, 'phone' => '0300-1000003']),
        ];

        // The two seeded by DatabaseSeeder may predate the commission fields.
        $dispatchers[0]->update([...$pct, 'commission_percentage' => 10]);

        // One deactivated account to test the login block.
        User::query()->where('email', "former@{$this->domain}")->exists()
            || User::factory()->create(['name' => 'Former Dispatcher', 'email' => "former@{$this->domain}", 'status' => UserStatus::Inactive])->assignRole('dispatcher');

        return ['admin' => $admin, 'accountant' => $accountant, 'dispatchers' => $dispatchers];
    }

    /** @return list<Company> */
    private function companies(): array
    {
        $rows = [
            ['Ridgeline Freight LLC', 'MC-701001', 10.00, 'Weekly'],
            ['Summit Haulers Inc', 'MC-701002', 12.50, 'Weekly'],
            ['Lone Star Carriers', 'MC-701003', 8.00, 'Weekly'],
            ['Bluewater Transport', 'MC-701004', 15.00, 'Weekly'],
        ];

        $companies = array_map(fn (array $row): Company => Company::factory()->create([
            'name' => $row[0], 'mc_number' => $row[1], 'default_commission_percentage' => $row[2],
        ]), $rows);

        Company::factory()->inactive()->create(['name' => 'Dormant Trucking Co', 'mc_number' => 'MC-701099']);

        return $companies;
    }

    /**
     * @param  list<Company>  $companies
     * @return array<int, list<Driver>> company id => drivers
     */
    private function drivers(array $companies): array
    {
        $names = [
            ['Raja Khan', 'Bilal Ahmed', 'Usman Tariq', 'Hamza Ali'],
            ['John Miller', 'Carlos Diaz', 'Mike Turner', 'Dave Wilson'],
            ['Tom Bradley', 'Luis Garcia', 'Ray Johnson', 'Sam Carter'],
            ['Kevin Brooks', 'Omar Sheikh', 'Paul Evans', 'Nick Foster'],
        ];
        $types = EquipmentType::query()->pluck('name')->all();
        $out = [];
        $n = 100;

        foreach ($companies as $i => $company) {
            foreach ($names[$i] as $j => $name) {
                $out[$company->id][] = Driver::factory()->forCompany($company)->create([
                    'name' => $name,
                    'phone' => '+1 555 '.sprintf('%03d %04d', 200 + $i, 1000 + $j),
                    'truck_number' => 'TRK-'.(++$n),
                    'equipment_type' => $types[($i + $j) % count($types)],
                    // The last driver of the last company is inactive for filter testing.
                    'status' => ($i === 3 && $j === 3) ? DriverStatus::Inactive : DriverStatus::Available,
                ]);
            }
        }

        return $out;
    }

    /** @return list<Broker> */
    private function brokers(): array
    {
        $rows = [
            ['C.H. Robinson Demo', 'MC-550001', 'Quick Pay'],
            ['TQL Brokerage Demo', 'MC-550002', 'Net 30'],
            ['Echo Logistics Demo', 'MC-550003', 'Net 15'],
            ['Coyote Freight Demo', 'MC-550004', 'Quick Pay'],
            ['Landstar Demo', 'MC-550005', 'Net 30'],
        ];

        $brokers = array_map(fn (array $r): Broker => Broker::factory()->create([
            'company_name' => $r[0], 'broker_mc_number' => $r[1],
        ]), $rows);

        Broker::factory()->inactive()->create([
            'company_name' => 'Dormant Freight Demo', 'broker_mc_number' => 'MC-550099',
            'credit_notes' => 'No longer working with this broker.',
        ]);

        return $brokers;
    }

    /** @return array{Account, Account, Account} */
    private function accounts(): array
    {
        $service = app(AccountService::class);
        $make = fn (string $name, AccountType $type, float $opening) => $service->create([
            'name' => $name, 'type' => $type, 'opening_balance' => $opening, 'status' => AccountStatus::Active,
        ]);

        $accounts = [
            $make('Office Cash (PKR)', AccountType::Cash, 150000),
            $make('HBL Current Account', AccountType::Bank, 2500000),
            $make('Meezan Business Account', AccountType::Bank, 1200000),
        ];

        Account::factory()->create(['name' => 'Old Savings (closed)', 'type' => AccountType::Bank, 'opening_balance' => 0, 'current_balance' => 0, 'status' => AccountStatus::Inactive]);

        return $accounts;
    }

    /* ───────────────────────── loads ───────────────────────── */

    /**
     * 26 loads across every stage, booked through the real services so history,
     * driver status, notifications and (for paid ones) commissions all exist.
     *
     * @param  array<string, mixed>  $users
     * @param  list<Company>  $companies
     * @param  array<int, list<Driver>>  $drivers
     * @param  list<Broker>  $brokers
     * @return list<Load>
     */
    private function loads(array $users, array $companies, array $drivers, array $brokers): array
    {
        $loadService = app(LoadService::class);
        $statusService = app(LoadStatusService::class);

        // target status per load, oldest first: paid ones are the oldest.
        $plan = array_merge(
            array_fill(0, 12, LoadStatus::Paid),
            array_fill(0, 5, LoadStatus::Delivered),
            array_fill(0, 4, LoadStatus::Loaded),
            array_fill(0, 5, LoadStatus::Booked),
        );

        $loads = [];
        $usedActiveDrivers = [];

        foreach ($plan as $i => $target) {
            $company = $companies[$i % count($companies)];
            $dispatcher = $users['dispatchers'][$i % count($users['dispatchers'])];
            $pool = $drivers[$company->id];
            $driver = $pool[$i % 3]; // never the inactive one
            [$from, $to] = $this->route($i);
            $miles = $this->miles($from, $to);
            $rate = round($miles * (2.0 + mt_rand(0, 120) / 100) / 50) * 50;

            // Keep one active load per driver so On Load status stays meaningful.
            if ($target !== LoadStatus::Paid) {
                if (isset($usedActiveDrivers[$driver->id])) {
                    $driver = collect($pool)->first(fn (Driver $d): bool => ! isset($usedActiveDrivers[$d->id]) && $d->status !== DriverStatus::Inactive) ?? $driver;
                }
                $usedActiveDrivers[$driver->id] = true;
            }

            $daysAgo = match ($target) {
                LoadStatus::Paid => 35 - $i * 2,
                default => max(-4, 12 - $i),
            };
            $pickup = now()->subDays($daysAgo)->setTime(8, 0);

            $load = $loadService->create([
                'company_id' => $company->id,
                'broker_id' => $brokers[$i % count($brokers)]->id,
                'driver_id' => $driver->id,
                'equipment_type' => $driver->equipment_type,
                'origin_address' => $from[0], 'origin_lat' => $from[1], 'origin_lng' => $from[2],
                'destination_address' => $to[0], 'destination_lat' => $to[1], 'destination_lng' => $to[2],
                'total_miles' => $miles,
                'weight_lbs' => mt_rand(8, 44) * 1000,
                'pickup_at' => $pickup,
                'dropoff_at' => $pickup->copy()->addDays(max(1, (int) ceil($miles / 500))),
                'rate' => $rate,
                'dispatcher_id' => $dispatcher->id,
                'notes' => $i % 4 === 0 ? 'Driver to call 1 hour before delivery.' : null,
            ], $dispatcher);

            $load->refresh();

            while ($load->status !== $target) {
                $statusService->changeStatus($load, $load->status->next(), $dispatcher, 'Demo progression');
                $load->refresh();
            }

            $loads[] = $load;
        }

        // A load with a per-load commission override (what an admin may set).
        $override = $loads[count($loads) - 1];
        $override->update(['company_commission_percentage' => 20, 'company_commission_amount' => round((float) $override->rate * 0.20, 2)]);

        return $loads;
    }

    /** @return array{array{string, float, float}, array{string, float, float}} */
    private function route(int $i): array
    {
        $a = self::CITIES[$i % count(self::CITIES)];
        $b = self::CITIES[($i + 3 + $i % 4) % count(self::CITIES)];

        return $a === $b ? [$a, self::CITIES[($i + 1) % count(self::CITIES)]] : [$a, $b];
    }

    /** Great-circle miles plus road factor — never calls the paid Maps API. */
    private function miles(array $a, array $b): float
    {
        [$lat1, $lng1, $lat2, $lng2] = array_map('deg2rad', [$a[1], $a[2], $b[1], $b[2]]);
        $d = 2 * asin(sqrt(sin(($lat2 - $lat1) / 2) ** 2 + cos($lat1) * cos($lat2) * sin(($lng2 - $lng1) / 2) ** 2));

        return round(3959 * $d * 1.2, 1);
    }

    /** @param  list<Load>  $loads */
    private function attachments(array $loads, User $uploader): void
    {
        foreach (array_slice($loads, 0, 6) as $i => $load) {
            foreach ([AttachmentType::RateConfirmation, AttachmentType::Bol, AttachmentType::Pod] as $j => $type) {
                if ($j > $i % 3) {
                    continue;
                }

                $path = "load-attachments/demo-{$load->id}-{$type->value}.pdf";
                Storage::disk('local')->put($path, "%PDF-1.4\n% Demo {$type->value} for {$load->reference_number}\n%%EOF");

                LoadAttachment::create(['load_id' => $load->id, 'type' => $type, 'file_path' => $path, 'uploaded_by' => $uploader->id]);
            }
        }
    }

    /* ───────────────────────── money ───────────────────────── */

    /**
     * @param  array<string, mixed>  $users
     * @param  list<Account>  $accounts
     * @param  list<Load>  $loads
     * @return Load the booked load carrying an approved JV (cancelled later)
     */
    private function journalVouchers(array $users, array $accounts, array $loads): Load
    {
        [$cash, $hbl, $meezan] = $accounts;
        $service = app(JournalVoucherService::class);
        $admin = $users['admin'];
        $make = fn (array $d) => $service->create(['date' => now()->subDays(mt_rand(1, 10))->toDateString(), ...$d], $users['accountant']);

        $make(['debit_account_id' => $cash->id, 'credit_account_id' => $hbl->id, 'amount' => 25000, 'narration' => 'Cash withdrawal for office petty cash']);
        $make(['debit_account_id' => $meezan->id, 'credit_account_id' => $cash->id, 'amount' => 12000, 'narration' => 'Cash deposited to Meezan']);

        $approved = $make(['debit_account_id' => $hbl->id, 'credit_account_id' => $meezan->id, 'amount' => 100000, 'narration' => 'Transfer between bank accounts']);
        $service->approve($approved, $admin);

        $voided = $make(['debit_account_id' => $cash->id, 'credit_account_id' => $meezan->id, 'amount' => 5000, 'narration' => 'Entered by mistake']);
        $service->approve($voided, $admin);
        $service->void($voided->refresh(), $admin);

        $booked = collect($loads)->first(fn (Load $l): bool => $l->status === LoadStatus::Booked);
        $tied = $make(['debit_account_id' => $hbl->id, 'credit_account_id' => $cash->id, 'amount' => 8000, 'narration' => "Advance for {$booked->reference_number}", 'load_id' => $booked->id]);
        $service->approve($tied, $admin);

        return $booked;
    }

    /** @param  list<Company>  $companies */
    private function companyReceipts(array $companies, User $admin, Account $meezan, Account $hbl): void
    {
        $payments = app(CompanyPaymentService::class);
        $week = [now()->subWeeks(2)->startOfWeek()->toDateString(), now()->subWeeks(2)->endOfWeek()->toDateString()];

        $owed = fn (Company $c): float => $payments->balance($c)['outstanding'];
        $receive = fn (Company $c, float $amount, string $ref) => $payments->create([
            'company_id' => $c->id, 'period_from' => $week[0], 'period_to' => $week[1],
            'amount_received' => round($amount, 2), 'payment_date' => now()->subDays(3)->toDateString(), 'reference' => $ref,
        ], $admin);

        // 1: paid in full.  2: paid half (partially settled).  3: overpaid (advance).  4: nothing yet.
        $full = $receive($companies[0], $owed($companies[0]), 'WIRE-10001');
        $half = $receive($companies[1], $owed($companies[1]) / 2, 'WIRE-10002');
        $over = $receive($companies[2], $owed($companies[2]) + 150, 'WIRE-10003');

        $payments->convert($full, [
            'usd_amount' => $full->amount_received, 'conversion_rate' => 281.25,
            'deposit_account_id' => $meezan->id, 'conversion_date' => now()->subDays(2)->toDateString(),
            'tax_applicable' => false, 'conversion_notes' => 'Converted at open-market rate',
        ], $admin);

        // The half-paid company settles the rest a few days later.
        $payments->create([
            'company_id' => $companies[1]->id, 'period_from' => $week[0], 'period_to' => $week[1],
            'amount_received' => $owed($companies[1]), 'payment_date' => now()->subDay()->toDateString(), 'reference' => 'WIRE-10004',
            'convert' => true, 'conversion_rate' => 280.80, 'deposit_account_id' => $hbl->id,
            'tax_applicable' => true, 'tax_type' => TaxType::Percentage->value, 'tax_value' => 1.5,
        ], $admin);

        // $over is deliberately left unconverted so the convert form has a candidate.
        unset($over);
    }

    /**
     * Dispatcher one is paid part of what they are owed, dispatcher two in full,
     * dispatcher three more than they have earned (an advance).
     *
     * @param  array<string, mixed>  $users
     */
    private function commissionPayouts(array $users, Account $bank): void
    {
        $payouts = app(CommissionPayoutService::class);
        $ledger = app(LedgerService::class);

        foreach ([0 => 0.6, 1 => 1.0, 2 => null] as $index => $share) {
            $dispatcher = $users['dispatchers'][$index];
            $owed = $ledger->position($dispatcher)['outstanding'];
            $amount = $share === null ? $owed + 25 : round($owed * $share, 2);

            if ($amount <= 0) {
                continue;
            }

            $payouts->create($dispatcher, [
                'usd_amount' => $amount, 'conversion_rate' => 281.00, 'payment_account_id' => $bank->id,
                'paid_at' => now()->subDay()->toDateString(), 'reference' => 'TRF-'.(9000 + $index),
                'notes' => $share === null ? 'Advance ahead of next loads' : 'Demo payout',
            ], $users['admin']);
        }
    }

    /** @param  list<Account>  $accounts */
    private function expenses(User $accountant, array $accounts): void
    {
        $service = app(ExpenseService::class);
        ExpenseCategory::seedStarter();
        $categories = ExpenseCategory::query()->ordered()->get();
        $payees = ['Salaries' => 'Office staff', 'Rent' => 'Landlord', 'Electricity & utilities' => 'K-Electric', 'Internet & phone' => 'PTCL',
            'Load board subscriptions' => 'DAT Solutions', 'Software & subscriptions' => 'Google Workspace', 'Marketing & ads' => 'Meta Ads'];

        foreach ($categories as $i => $category) {
            foreach ([0, 1] as $k) {
                $service->create([
                    'expense_category_id' => $category->id,
                    'expense_date' => now()->subDays($i * 3 + $k * 9 + 1)->toDateString(),
                    'amount' => mt_rand(20, 900) * ($category->name === 'Salaries' ? 100 : 10),
                    'payee' => $payees[$category->name] ?? null,
                    'reference' => $k ? null : 'INV-'.(1000 + $i),
                    'payment_method' => ['cash', 'bank_transfer', 'online'][($i + $k) % 3],
                    'account_id' => $accounts[($i + $k) % 3]->id,
                    'notes' => $category->name.($k ? ' (previous month)' : ''),
                ], $accountant);
            }
        }

        // Exercise the reversal paths: one edited (amount + account), one deleted.
        $edited = Expense::query()->orderBy('id')->first();
        $service->update($edited, [
            'expense_category_id' => $edited->expense_category_id, 'payment_method' => $edited->payment_method?->value ?? 'cash', 'expense_date' => $edited->expense_date->toDateString(),
            'amount' => (float) $edited->amount + 500, 'account_id' => $accounts[1]->id, 'notes' => 'Corrected amount',
        ], $accountant);

        $service->delete(Expense::query()->orderByDesc('id')->first(), $accountant);
    }

    /**
     * Cancellations from every stage, plus one settled load cancelled with a
     * settlement adjustment (credit line carried to the next period).
     *
     * @param  list<Load>  $loads
     * @param  array<string, mixed>  $users
     */
    private function cancellations(array $loads, array $users, Load $withJournalVoucher): void
    {
        $cancel = app(LoadCancellationService::class);
        $admin = $users['admin'];
        $dispatcher = $users['dispatchers'][0];

        $byStatus = fn (LoadStatus $s) => collect($loads)->filter(fn (Load $l): bool => $l->refresh()->status === $s)->values();

        // Booked one carries the approved JV → that JV is voided by the listener.
        $cancel->cancel($withJournalVoucher, $dispatcher, 'Broker cancelled before pickup');
        $cancel->cancel($byStatus(LoadStatus::Booked)->first(), $dispatcher, 'Customer postponed shipment');
        $cancel->cancel($byStatus(LoadStatus::Loaded)->first(), $dispatcher, 'Truck breakdown at shipper');
        $cancel->cancel($byStatus(LoadStatus::Delivered)->first(), $admin, 'Shipper closed — load not ready');

        // Settled, commission not yet paid out: cancel with an adjustment.
        $settled = $byStatus(LoadStatus::Paid)->first(function (Load $l): bool {
            $commission = Commission::query()->where('load_id', $l->id)->first();

            return $l->payments()->where('total_due', '>', 0)->exists()
                && ($commission === null || $commission->status === CommissionStatus::Accrued);
        });

        if ($settled) {
            $cancel->cancel($settled, $admin, 'Broker disputed delivery — refund agreed', adjustSettlement: true);
        }
    }
}
