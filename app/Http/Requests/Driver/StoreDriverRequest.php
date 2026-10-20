<?php

namespace App\Http\Requests\Driver;

use App\Enums\DriverStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:` middleware on routes/modules/drivers.php.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
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

        $this->merge(collect(['phone'])
            ->filter(fn (string $key): bool => $this->has($key))
            ->mapWithKeys(fn (string $key): array => [$key => $clean($this->input($key))])
            ->all());
    }

    public function rules(): array
    {
        return [
            'company_id' => [
                'required',
                'integer',
                \App\Support\Tenancy\TenantRule::exists('companies', 'id')->whereNull('deleted_at'),
                // A dispatcher can only put a driver under a company allocated to them
                // (an existing driver keeps the company it already has).
                fn (string $attribute, mixed $value, \Closure $fail) => $this->user()?->isScopedToOwnRecords()
                    && ! $this->user()->companies()->whereKey($value)->exists()
                    && (int) $value !== (int) $this->route('driver')?->company_id
                    && $fail('That company is not allocated to you.'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'truck_number' => ['required', 'string', 'max:64'],
            'trailer_number' => ['nullable', 'string', 'max:64'],
            'truck_length_ft' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'truck_width_ft' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'truck_height_ft' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'truck_max_weight_lbs' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'truck_length_ft' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'truck_width_ft' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'truck_height_ft' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'truck_max_weight_lbs' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'equipment_type' => ['required', 'string', \App\Support\Tenancy\TenantRule::exists('equipment_types', 'name')],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+()\-.\s]+$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'status' => ['required', Rule::enum(DriverStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
