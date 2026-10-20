<?php

namespace App\Http\Requests\Company;

use App\Enums\CompanyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:` middleware on routes/modules/companies.php.
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
            ...\App\Services\Support\CustomFields::rules('company'),
            'name' => ['required', 'string', 'max:255'],
            // Unique across soft-deleted rows too: a retired MC number must not
            // be handed to a different carrier.
            'mc_number' => ['required', 'string', 'max:32', \App\Support\Tenancy\TenantRule::unique('companies', 'mc_number')],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-.\s]+$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'default_commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', Rule::enum(CompanyStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
