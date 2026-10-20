<?php

namespace App\Http\Requests\User;

use App\Enums\CommissionType;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:` middleware on routes/modules/users.php.
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'status' => ['required', Rule::enum(UserStatus::class)],
            // Nullable so the column default carries it: a caller that never
            // mentions the type is a percentage cut, which is what the
            // migration defaults every user to.
            'commission_type' => ['nullable', Rule::enum(CommissionType::class)],
            // Only the field the chosen type actually uses is required, so a
            // half-filled switch cannot be saved.
            'commission_percentage' => ['required_if:commission_type,percentage', 'nullable', 'numeric', 'between:0,100'],
            // Companies a dispatcher may book for.
            'companies' => ['nullable', 'array'],
            'companies.*' => ['integer', Rule::exists('companies', 'id')],
            'commission_fixed_amount' => ['required_if:commission_type,fixed', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
