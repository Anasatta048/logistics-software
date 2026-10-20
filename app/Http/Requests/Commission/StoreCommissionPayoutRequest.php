<?php

namespace App\Http\Requests\Commission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCommissionPayoutRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:pay` middleware on routes/modules/commissions.php.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The PKR amount is deliberately not a field: the service computes it from
     * the USD amount and the rate, so a tampered form cannot pay out more than
     * the rate on it says. The USD amount is free on purpose — part payments and
     * advances are both legitimate; the ledger records which one it was.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dispatcher_id' => ['required', 'integer', \App\Support\Tenancy\TenantRule::exists('users', 'id')],
            'usd_amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'conversion_rate' => ['required', 'numeric', 'min:0.0001', 'max:999999'],
            'payment_method' => ['required', Rule::enum(\App\Enums\PaymentMethod::class)],
            'payment_account_id' => ['nullable', \App\Support\Tenancy\TenantRule::exists('accounts', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'paid_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['payment_account_id' => 'account', 'paid_at' => 'paid date', 'usd_amount' => 'amount'];
    }
}
