<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:` middleware on routes/modules/expenses.php.
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
            'expense_category_id' => ['required', \App\Support\Tenancy\TenantRule::exists('expense_categories', 'id')->where('is_active', true)],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'payee' => ['nullable', 'string', 'max:120'],
            'reference' => ['nullable', 'string', 'max:60'],
            'payment_method' => ['required', Rule::enum(\App\Enums\PaymentMethod::class)],
            // Optional: an expense can be recorded without touching any account.
            'account_id' => ['nullable', \App\Support\Tenancy\TenantRule::exists('accounts', 'id')->where('status', 'active')],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['account_id' => 'account', 'expense_category_id' => 'category'];
    }
}
