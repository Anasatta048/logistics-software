<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalVoucherRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:` middleware on routes/modules/journal-vouchers.php.
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
            'date' => ['required', 'date'],
            'debit_account_id' => ['required', 'different:credit_account_id', \App\Support\Tenancy\TenantRule::exists('accounts', 'id')->where('status', 'active')],
            'credit_account_id' => ['required', \App\Support\Tenancy\TenantRule::exists('accounts', 'id')->where('status', 'active')],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'narration' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'load_id' => ['nullable', \App\Support\Tenancy\TenantRule::exists('loads', 'id')],
        ];
    }

    public function attributes(): array
    {
        return [
            'debit_account_id' => 'debit account',
            'credit_account_id' => 'credit account',
        ];
    }
}
