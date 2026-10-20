<?php

namespace App\Http\Requests\CompanyPayment;

use App\Enums\TaxType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Converting a payment that is already recorded. Authorization is the
 * `can:convert` middleware on routes/modules/company-payments.php.
 */
final class ConvertCompanyPaymentRequest extends FormRequest
{
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
            'usd_amount' => ['nullable', 'numeric', 'min:0.01', 'max:99999999'],
            'conversion_rate' => ['required', 'numeric', 'min:0.0001', 'max:999999'],
            'deposit_account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'conversion_date' => ['required', 'date'],
            'tax_applicable' => ['required', 'boolean'],
            // Both tax fields are meaningless without the toggle on.
            'tax_type' => ['nullable', 'required_if:tax_applicable,1', Rule::enum(TaxType::class)],
            'tax_value' => ['nullable', 'required_if:tax_applicable,1', 'numeric', 'min:0', 'max:99999999'],
            'conversion_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tax_type.required_if' => 'Choose how the tax is applied.',
            'tax_value.required_if' => 'Enter the tax percentage or the fixed amount.',
        ];
    }
}
