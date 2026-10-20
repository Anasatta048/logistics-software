<?php

namespace App\Http\Requests\Account;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:` middleware on routes/modules/accounts.php.
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'opening_balance' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'status' => ['required', Rule::enum(AccountStatus::class)],
        ];
    }
}
