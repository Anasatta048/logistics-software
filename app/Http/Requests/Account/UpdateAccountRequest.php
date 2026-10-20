<?php

namespace App\Http\Requests\Account;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `opening_balance` is intentionally not validated here: AccountService::update()
 * strips it before saving, since changing it after transactions exist would
 * desync the ledger.
 */
class UpdateAccountRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'status' => ['required', Rule::enum(AccountStatus::class)],
        ];
    }
}
