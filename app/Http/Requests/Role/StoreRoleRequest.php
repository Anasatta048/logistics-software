<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:` middleware on routes/modules/roles.php.
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
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', \App\Support\Tenancy\TenantRule::roleUnique()],
            // Optional on create: a role can be set up first and given its
            // rights in the same form, or later.
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'Use lower case letters, numbers and dashes only (e.g. night-dispatcher).',
        ];
    }
}
