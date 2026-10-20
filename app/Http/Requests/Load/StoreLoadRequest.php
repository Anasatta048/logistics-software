<?php

namespace App\Http\Requests\Load;

use App\Enums\AttachmentType;
use App\Enums\BrokerStatus;
use App\Enums\DriverStatus;
use App\Models\Broker;
use App\Models\Driver;
use App\Models\User;
use App\Support\Timezones;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLoadRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    /**
     * A phone copied from a web page or chat often carries invisible characters
     * (non-breaking space, left-to-right mark, zero-width space) that look like a
     * normal number but fail the format rule. Normalise them before validating.
     */
    protected function prepareForValidation(): void
    {
        $clean = static fn (mixed $value): mixed => is_string($value)
            ? trim(preg_replace(['/[\p{Cf}\x{FEFF}]/u', '/[\p{Zs}\t]+/u'], ['', ' '], $value))
            : $value;

        $this->merge(collect(['broker_phone', 'reference_number'])
            ->filter(fn (string $key): bool => $this->has($key))
            ->mapWithKeys(fn (string $key): array => [$key => $clean($this->input($key))])
            ->all());
    }

    public function rules(): array
    {
        return [
            // Either an existing broker (picked by MC number) or the details of a
            // new one - the form sends one or the other, never both.
            // Optional: left blank, a DEL-000123 style number is generated.
            'reference_number' => [
                'nullable', 'string', 'max:64',
                \App\Support\Tenancy\TenantRule::unique('loads', 'reference_number')->ignore($this->route('load')),
            ],
            'broker_id' => [
                'nullable',
                'integer',
                // soft-deleted brokers are not selectable
                \App\Support\Tenancy\TenantRule::exists('brokers', 'id')->whereNull('deleted_at'),
                fn (string $attribute, mixed $value, \Closure $fail) => $this->brokerIsBlocked($value) && $fail('That broker is inactive.'),
            ],
            'new_broker.broker_mc_number' => [
                Rule::excludeIf(fn () => filled($this->input('broker_id'))),
                Rule::requiredIf(fn () => blank($this->input('broker_id'))),
                'string', 'max:32',
                \App\Support\Tenancy\TenantRule::unique('brokers', 'broker_mc_number'),
            ],
            'new_broker.company_name' => [Rule::excludeIf(fn () => filled($this->input('broker_id'))), 'required_with:new_broker.broker_mc_number', 'nullable', 'string', 'max:255'],
            // Who to call at the broker for THIS load; the broker record only holds the company.
            'broker_contact_person' => ['nullable', 'string', 'max:255'],
            'broker_phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+()\-.\s]+$/'],
            'broker_email' => ['nullable', 'email', 'max:255'],
            // The company is picked first on the form and the driver list is
            // filtered to it, so the driver must belong to the one submitted -
            // the filter is a convenience, this is the guarantee.
            'company_id' => [
                'required',
                'integer',
                \App\Support\Tenancy\TenantRule::exists('companies', 'id')->whereNull('deleted_at'),
                fn (string $attribute, mixed $value, \Closure $fail) => $this->companyIsNotAllocated($value) && $fail('That company is not allocated to the dispatcher.'),
            ],
            'driver_id' => [
                'nullable',
                'integer',
                \App\Support\Tenancy\TenantRule::exists('drivers', 'id')
                    ->whereNull('deleted_at')
                    ->where(fn ($query) => $query->where('company_id', $this->integer('company_id'))),
                fn (string $attribute, mixed $value, \Closure $fail) => $this->driverIsInactive($value) && $fail('That driver is inactive.'),
            ],
            // A driver not on file yet is added under the chosen company on save.
            'new_driver.name' => [Rule::excludeIf(fn () => filled($this->input('driver_id'))), 'nullable', 'string', 'max:255'],
            'new_driver.phone' => [Rule::excludeIf(fn () => filled($this->input('driver_id'))), 'required_with:new_driver.name', 'nullable', 'string', 'max:32', 'regex:/^[0-9+()\-.\s]+$/'],
            'new_driver.truck_number' => [Rule::excludeIf(fn () => filled($this->input('driver_id'))), 'required_with:new_driver.name', 'nullable', 'string', 'max:64'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['required', 'string', \App\Support\Tenancy\TenantRule::exists('equipment_types', 'name')],
            'origin_address' => ['required', 'string', 'max:255'],
            'origin_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'origin_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'destination_address' => ['required', 'string', 'max:255'],
            'destination_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'destination_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'total_miles' => ['required', 'numeric', 'min:0', 'max:99999'],
            'weight_lbs' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'length_ft' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'width_ft' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'height_ft' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'pickup_timezone' => ['nullable', 'string', Rule::in(Timezones::usIdentifiers())],
            'pickup_at' => ['required', 'date'],
            'dropoff_timezone' => ['nullable', 'string', Rule::in(Timezones::usIdentifiers())],
            // Order is checked in after(): the two times may be in different zones.
            'dropoff_at' => ['required', 'date'],
            'rate' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'dispatcher_id' => ['nullable', 'integer', \App\Support\Tenancy\TenantRule::exists('users', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
            // Documents dropped on the form; one type per file, same order.
            // At least one is needed to create a load; editing may add more but never has to.
            'attachments' => [$this instanceof UpdateLoadRequest ? 'nullable' : 'required', 'array', 'min:1', 'max:10'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'attachment_types.*' => ['required_with:attachments', Rule::enum(AttachmentType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reference_number.unique' => 'Another load already uses this load number.',
            'company_id.exists' => 'That company is not available.',
            'new_broker.broker_mc_number.required' => "Enter the broker's MC number.",
            'new_broker.broker_mc_number.unique' => "A broker with this MC number already exists, pick it from the list.",
            'attachments.required' => 'Attach at least one document (rate confirmation, BOL, etc.).',
            'attachments.min' => 'Attach at least one document (rate confirmation, BOL, etc.).',
            'new_broker.company_name.required_with' => "Enter the broker's company name.",
            'broker_phone.required' => "Enter the broker contact's phone number.",
            'new_driver.phone.required_with' => "Enter the new driver's phone number.",
            'new_driver.truck_number.required_with' => "Enter the new driver's truck number.",
            'driver_id.exists' => 'That driver does not belong to the selected company.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Adding a broker or driver on the fly is still creating one, so it needs
     * the same right as the master-data screens.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $user = $this->user();

            if (filled($this->input('pickup_at')) && filled($this->input('dropoff_at')) && ! $validator->errors()->hasAny(['pickup_at', 'dropoff_at'])) {
                $pickup = Timezones::parseIn((string) $this->input('pickup_at'), $this->input('pickup_timezone'));
                $dropoff = Timezones::parseIn((string) $this->input('dropoff_at'), $this->input('dropoff_timezone'));

                if ($dropoff->lt($pickup)) {
                    $validator->errors()->add('dropoff_at', 'The drop-off time cannot be before the pick-up time.');
                }
            }

            if (blank($this->input('broker_id')) && filled($this->input('new_broker.broker_mc_number')) && ! $user?->can('brokers.create')) {
                $validator->errors()->add('new_broker.broker_mc_number', 'You cannot add new brokers - pick an existing one.');
            }

            if (blank($this->input('driver_id')) && filled($this->input('new_driver.name')) && ! $user?->can('drivers.create')) {
                $validator->errors()->add('new_driver.name', 'You cannot add new drivers - pick an existing one.');
            }
        }];
    }

    /**
     * An inactive broker can stay on a load that already has it; it just
     * cannot be newly picked.
     */
    private function brokerIsBlocked(mixed $id): bool
    {
        $current = $this->route('load')?->broker_id;

        return (int) $id !== (int) $current
            && Broker::query()->whereKey($id)->where('status', BrokerStatus::Inactive)->exists();
    }

    /**
     * A dispatcher can only book for the companies allocated to them; a load
     * that already has another company keeps it.
     */
    private function companyIsNotAllocated(mixed $id): bool
    {
        $owner = User::allocationOwner($this->user(), $this->input('dispatcher_id'));

        return $owner !== null
            && (int) $id !== (int) $this->route('load')?->company_id
            && ! $owner->companies()->whereKey($id)->exists();
    }

    private function driverIsInactive(mixed $id): bool
    {
        $current = $this->route('load')?->driver_id;

        return (int) $id !== (int) $current
            && Driver::query()->whereKey($id)->where('status', DriverStatus::Inactive)->exists();
    }
}
