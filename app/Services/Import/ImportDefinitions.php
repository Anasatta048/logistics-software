<?php

namespace App\Services\Import;

use App\Enums\BrokerStatus;
use App\Enums\CompanyStatus;
use App\Enums\DriverStatus;
use App\Enums\LoadStatus;
use App\Enums\PaymentFrequency;
use App\Models\Broker;
use App\Models\Company;
use App\Models\Driver;
use App\Models\EquipmentType;
use App\Models\Load;
use App\Models\User;
use App\Services\Support\NumberSequence;
use App\Support\Settings\TenantSettings;
use App\Support\Tenancy\TenantRule;
use App\Support\Timezones;
use Illuminate\Validation\Rule;

/**
 * What can be imported and how: the columns a spreadsheet may carry (with the
 * header names we recognise), the validation of one row, and how a valid row
 * becomes a record. Imports bring in *history*: they create the records but do
 * not fire the booking flow (no notifications, commissions, invoices or ledger
 * entries), so past data never changes today's balances.
 */
final class ImportDefinitions
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return [
            'companies' => [
                'label' => 'Carrier companies',
                'note' => 'The carriers whose trucks you dispatch. Import these first.',
                'columns' => [
                    'name' => ['Company name', true, ['company', 'company name', 'carrier', 'carrier name', 'name'], 'Lone Star Carriers'],
                    'mc_number' => ['MC number', true, ['mc', 'mc number', 'mc no', 'mc#'], 'MC-123456'],
                    'contact_person' => ['Contact person', false, ['contact', 'contact person', 'owner'], 'John Smith'],
                    'phone' => ['Phone', false, ['phone', 'mobile', 'tel'], '(512) 555-0100'],
                    'email' => ['Email', false, ['email', 'e-mail'], 'john@example.com'],
                    'address' => ['Address', false, ['address'], 'Austin, TX'],
                    'default_commission_percentage' => ['Service fee %', false, ['fee', 'fee %', 'commission', 'commission %', 'service fee'], '8'],
                    'payment_frequency' => ['Billing frequency', false, ['frequency', 'billing frequency', 'payment frequency'], 'weekly'],
                    'status' => ['Status', false, ['status'], 'active'],
                    'notes' => ['Notes', false, ['notes', 'remarks'], ''],
                ],
            ],
            'brokers' => [
                'label' => 'Brokers',
                'note' => 'Freight brokers you haul for.',
                'columns' => [
                    'company_name' => ['Broker name', true, ['broker', 'broker name', 'company', 'company name', 'name'], 'Acme Freight Brokerage'],
                    'broker_mc_number' => ['MC number', true, ['mc', 'mc number', 'broker mc', 'mc no', 'mc#'], 'MC-998877'],
                    'credit_notes' => ['Credit notes', false, ['credit notes', 'notes', 'remarks'], 'Pays net 30'],
                    'status' => ['Status', false, ['status'], 'active'],
                ],
            ],
            'drivers' => [
                'label' => 'Drivers',
                'note' => 'Needs the carrier company imported first (matched by its MC number).',
                'columns' => [
                    'name' => ['Driver name', true, ['driver', 'driver name', 'name'], 'Mike Johnson'],
                    'company_mc_number' => ['Carrier MC number', true, ['company mc', 'carrier mc', 'mc', 'mc number'], 'MC-123456'],
                    'phone' => ['Phone', true, ['phone', 'mobile', 'tel'], '(512) 555-0111'],
                    'truck_number' => ['Truck number', true, ['truck', 'truck number', 'truck no', 'unit'], 'TRK-12'],
                    'trailer_number' => ['Trailer number', false, ['trailer', 'trailer number'], 'TRL-4'],
                    'equipment_type' => ['Equipment type', false, ['equipment', 'equipment type', 'type'], 'Dry Van'],
                    'email' => ['Email', false, ['email', 'e-mail'], ''],
                    'status' => ['Status', false, ['status'], 'available'],
                    'notes' => ['Notes', false, ['notes', 'remarks'], ''],
                ],
            ],
            'loads' => [
                'label' => 'Loads (history)',
                'note' => 'Past loads for your records and reports. They do not create commissions, invoices or ledger entries.',
                'columns' => [
                    'reference_number' => ['Load number', false, ['load', 'load number', 'load no', 'reference', 'reference number', 'ref'], 'LD-0001'],
                    'broker_mc_number' => ['Broker MC number', true, ['broker mc', 'broker', 'mc', 'broker mc number'], 'MC-998877'],
                    'broker_name' => ['Broker name (if new)', false, ['broker name'], 'Acme Freight Brokerage'],
                    'company_mc_number' => ['Carrier MC number', false, ['carrier mc', 'company mc', 'carrier', 'company'], 'MC-123456'],
                    'driver_name' => ['Driver name', false, ['driver', 'driver name'], 'Mike Johnson'],
                    'equipment_type' => ['Equipment type', false, ['equipment', 'equipment type'], 'Dry Van'],
                    'origin_address' => ['Origin', true, ['origin', 'from', 'pickup', 'pickup address', 'origin address'], 'Dallas, TX'],
                    'destination_address' => ['Destination', true, ['destination', 'to', 'delivery', 'drop', 'destination address'], 'Atlanta, GA'],
                    'total_miles' => ['Miles', true, ['miles', 'total miles', 'distance'], '780'],
                    'weight_lbs' => ['Weight (lbs)', false, ['weight', 'weight lbs'], '42000'],
                    'pickup_at' => ['Pickup date/time', true, ['pickup date', 'pickup at', 'pickup time', 'pickup'], '2026-09-14 08:00'],
                    'dropoff_at' => ['Delivery date/time', true, ['delivery date', 'dropoff', 'dropoff at', 'delivery time', 'delivery'], '2026-09-15 17:00'],
                    'rate' => ['Rate ($)', true, ['rate', 'amount', 'freight', 'gross'], '2400'],
                    'company_commission_percentage' => ['Service fee %', false, ['fee', 'fee %', 'commission', 'commission %'], '8'],
                    'status' => ['Status', false, ['status'], 'paid'],
                    'dispatcher_email' => ['Dispatcher email', false, ['dispatcher', 'dispatcher email'], 'dispatcher@example.com'],
                    'notes' => ['Notes', false, ['notes', 'remarks'], ''],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function get(string $entity): array
    {
        return self::all()[$entity] ?? abort(404);
    }

    /**
     * Validation rules for one cleaned row.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $entity): array
    {
        return match ($entity) {
            'companies' => [
                'name' => ['required', 'string', 'max:255'],
                'mc_number' => ['required', 'string', 'max:32', TenantRule::unique('companies', 'mc_number')],
                'contact_person' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:40'],
                'email' => ['nullable', 'email', 'max:255'],
                'address' => ['nullable', 'string', 'max:500'],
                'default_commission_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'payment_frequency' => ['nullable', Rule::enum(PaymentFrequency::class)],
                'status' => ['nullable', Rule::enum(CompanyStatus::class)],
                'notes' => ['nullable', 'string', 'max:2000'],
            ],
            'brokers' => [
                'company_name' => ['required', 'string', 'max:255'],
                'broker_mc_number' => ['required', 'string', 'max:32', TenantRule::unique('brokers', 'broker_mc_number')],
                'credit_notes' => ['nullable', 'string', 'max:2000'],
                'status' => ['nullable', Rule::enum(BrokerStatus::class)],
            ],
            'drivers' => [
                'name' => ['required', 'string', 'max:255'],
                'company_mc_number' => ['required', 'string', 'max:32'],
                'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+()\-.\s]+$/'],
                'truck_number' => ['required', 'string', 'max:64'],
                'trailer_number' => ['nullable', 'string', 'max:64'],
                'equipment_type' => ['nullable', 'string', 'max:100'],
                'email' => ['nullable', 'email', 'max:255'],
                'status' => ['nullable', Rule::enum(DriverStatus::class)],
                'notes' => ['nullable', 'string', 'max:2000'],
            ],
            'loads' => [
                'reference_number' => ['nullable', 'string', 'max:64', TenantRule::unique('loads', 'reference_number')],
                'broker_mc_number' => ['required', 'string', 'max:32'],
                'broker_name' => ['nullable', 'string', 'max:255'],
                'company_mc_number' => ['nullable', 'string', 'max:32'],
                'driver_name' => ['nullable', 'string', 'max:255'],
                'equipment_type' => ['nullable', 'string', 'max:100'],
                'origin_address' => ['required', 'string', 'max:500'],
                'destination_address' => ['required', 'string', 'max:500'],
                'total_miles' => ['required', 'numeric', 'min:0', 'max:99999'],
                'weight_lbs' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
                'pickup_at' => ['required', 'date'],
                'dropoff_at' => ['required', 'date', 'after_or_equal:pickup_at'],
                'rate' => ['required', 'numeric', 'min:0', 'max:99999999'],
                'company_commission_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'status' => ['nullable', Rule::enum(LoadStatus::class)],
                'dispatcher_email' => ['nullable', 'email'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ],
            default => abort(404),
        };
    }

    /**
     * Turn a validated row into a saved record. Lookups (carriers, brokers,
     * equipment types, dispatchers) come from the shared per-run context.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $ctx  lookup tables built once per import
     *
     * @throws \InvalidArgumentException with a row-level message when a lookup fails
     */
    public static function persist(string $entity, array $row, array &$ctx): void
    {
        match ($entity) {
            'companies' => self::company($row, $ctx),
            'brokers' => self::broker($row, $ctx),
            'drivers' => self::driver($row, $ctx),
            'loads' => self::load($row, $ctx),
        };
    }

    /** @return array<string, mixed> lookup tables for a run */
    public static function context(): array
    {
        return [
            'companies' => Company::query()->pluck('id', 'mc_number')->mapWithKeys(fn ($id, $mc) => [mb_strtolower($mc) => $id])->all(),
            'company_fee' => Company::query()->pluck('default_commission_percentage', 'id')->all(),
            'brokers' => Broker::query()->pluck('id', 'broker_mc_number')->mapWithKeys(fn ($id, $mc) => [mb_strtolower($mc) => $id])->all(),
            'equipment' => EquipmentType::query()->pluck('name')->mapWithKeys(fn ($n) => [mb_strtolower($n) => $n])->all(),
            'dispatchers' => User::query()->pluck('id', 'email')->mapWithKeys(fn ($id, $e) => [mb_strtolower($e) => $id])->all(),
            'seen' => [],
        ];
    }

    private static function company(array $row, array &$ctx): void
    {
        $company = Company::create([
            'name' => $row['name'],
            'mc_number' => $row['mc_number'],
            'contact_person' => $row['contact_person'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'address' => $row['address'] ?? null,
            'default_commission_percentage' => $row['default_commission_percentage'] ?? TenantSettings::get('finance.default_fee_percentage', 10),
            'payment_frequency' => $row['payment_frequency'] ?? PaymentFrequency::Weekly->value,
            'payment_currency' => 'usd',
            'status' => $row['status'] ?? CompanyStatus::Active->value,
            'notes' => $row['notes'] ?? null,
        ]);

        $ctx['companies'][mb_strtolower($company->mc_number)] = $company->id;
        $ctx['company_fee'][$company->id] = $company->default_commission_percentage;
    }

    private static function broker(array $row, array &$ctx): void
    {
        $broker = Broker::create([
            'company_name' => $row['company_name'],
            'broker_mc_number' => $row['broker_mc_number'],
            'credit_notes' => $row['credit_notes'] ?? null,
            'status' => $row['status'] ?? BrokerStatus::Active->value,
        ]);

        $ctx['brokers'][mb_strtolower($broker->broker_mc_number)] = $broker->id;
    }

    private static function driver(array $row, array &$ctx): void
    {
        $companyId = $ctx['companies'][mb_strtolower($row['company_mc_number'])] ?? null;

        if ($companyId === null) {
            throw new \InvalidArgumentException("Carrier {$row['company_mc_number']} was not found. Import carriers first.");
        }

        Driver::create([
            'company_id' => $companyId,
            'name' => $row['name'],
            'phone' => $row['phone'],
            'truck_number' => $row['truck_number'],
            'trailer_number' => $row['trailer_number'] ?? null,
            'equipment_type' => self::equipment($row['equipment_type'] ?? null, $ctx),
            'email' => $row['email'] ?? null,
            'status' => $row['status'] ?? DriverStatus::Available->value,
            'notes' => $row['notes'] ?? null,
        ]);
    }

    private static function load(array $row, array &$ctx): void
    {
        $brokerKey = mb_strtolower($row['broker_mc_number']);
        $brokerId = $ctx['brokers'][$brokerKey] ?? null;

        if ($brokerId === null) {
            // A broker we have not seen is created from the sheet so history is not lost.
            $broker = Broker::create(['company_name' => $row['broker_name'] ?? $row['broker_mc_number'], 'broker_mc_number' => $row['broker_mc_number'], 'status' => BrokerStatus::Active->value]);
            $brokerId = $ctx['brokers'][$brokerKey] = $broker->id;
        }

        $companyId = null;
        if (filled($row['company_mc_number'] ?? null)) {
            $companyId = $ctx['companies'][mb_strtolower($row['company_mc_number'])] ?? null;
            if ($companyId === null) {
                throw new \InvalidArgumentException("Carrier {$row['company_mc_number']} was not found. Import carriers first.");
            }
        }

        $dispatcherId = null;
        if (filled($row['dispatcher_email'] ?? null)) {
            $dispatcherId = $ctx['dispatchers'][mb_strtolower($row['dispatcher_email'])] ?? null;
            if ($dispatcherId === null) {
                throw new \InvalidArgumentException("No user has the email {$row['dispatcher_email']}.");
            }
        }

        $rate = round((float) $row['rate'], 2);
        $miles = round((float) $row['total_miles'], 2);
        $pct = (float) ($row['company_commission_percentage'] ?? ($companyId ? ($ctx['company_fee'][$companyId] ?? 0) : TenantSettings::get('finance.default_fee_percentage', 0)));
        $reference = filled($row['reference_number'] ?? null) ? $row['reference_number'] : self::nextReference();

        $load = new Load();
        $load->forceFill([
            'reference_number' => $reference,
            'company_id' => $companyId,
            'broker_id' => $brokerId,
            'driver_name' => $row['driver_name'] ?? null,
            'equipment_type' => self::equipment($row['equipment_type'] ?? null, $ctx),
            'origin_address' => $row['origin_address'],
            'destination_address' => $row['destination_address'],
            'total_miles' => $miles,
            'weight_lbs' => $row['weight_lbs'] ?? 0,
            'pickup_at' => Timezones::parseUs((string) $row['pickup_at']),
            'pickup_timezone' => Timezones::us(),
            'dropoff_at' => Timezones::parseUs((string) $row['dropoff_at']),
            'dropoff_timezone' => Timezones::us(),
            'rate' => $rate,
            'rate_per_mile' => $miles > 0 ? round($rate / $miles, 2) : 0,
            'dispatcher_id' => $dispatcherId,
            'status' => $row['status'] ?? LoadStatus::Paid->value,
            'company_commission_percentage' => $pct,
            'company_commission_amount' => round($rate * $pct / 100, 2),
            'notes' => $row['notes'] ?? null,
        ])->save();
    }

    private static function nextReference(): string
    {
        return NumberSequence::format(
            (string) TenantSettings::get('numbering.load_prefix', 'LD'),
            NumberSequence::next('load', (int) TenantSettings::get('numbering.load_start_number', 1)),
            (int) TenantSettings::get('numbering.load_padding', 4),
        );
    }

    /** @param array<string, mixed> $ctx */
    private static function equipment(?string $name, array $ctx): string
    {
        if (blank($name)) {
            return (string) (array_values($ctx['equipment'])[0] ?? 'Other');
        }

        return $ctx['equipment'][mb_strtolower($name)]
            ?? throw new \InvalidArgumentException("Unknown equipment type “{$name}”. Add it under Equipment types first.");
    }
}
