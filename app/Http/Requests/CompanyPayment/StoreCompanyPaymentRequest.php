<?php

namespace App\Http\Requests\CompanyPayment;

use App\Enums\TaxType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyPaymentRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:` middleware on
     * routes/modules/company-payments.php.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', \App\Support\Tenancy\TenantRule::exists('companies', 'id')],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            // Which loads this receipt covers is decided by the service, oldest
            // unpaid commission first, so the form has nothing to send here.
            'amount_received' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::enum(\App\Enums\PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:5120'],

            // Optional: convert the USD to PKR in the same save.
            'convert' => ['nullable', 'boolean'],
            'conversion_rate' => ['exclude_unless:convert,1', 'required', 'numeric', 'min:0.0001', 'max:999999'],
            'deposit_account_id' => ['exclude_unless:convert,1', 'nullable', 'integer', \App\Support\Tenancy\TenantRule::exists('accounts', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'conversion_date' => ['exclude_unless:convert,1', 'nullable', 'date'],
            'tax_applicable' => ['exclude_unless:convert,1', 'nullable', 'boolean'],
            'tax_type' => ['exclude_unless:convert,1', 'nullable', 'required_if:tax_applicable,1', Rule::enum(TaxType::class)],
            'tax_value' => ['exclude_unless:convert,1', 'nullable', 'required_if:tax_applicable,1', 'numeric', 'min:0', 'max:99999999'],
            'conversion_notes' => ['exclude_unless:convert,1', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period_to.after_or_equal' => 'The period end cannot be before the period start.',
        ];
    }
}
