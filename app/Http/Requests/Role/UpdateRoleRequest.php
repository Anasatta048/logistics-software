<?php

namespace App\Http\Requests\Role;

use Illuminate\Validation\Rule;

final class UpdateRoleRequest extends StoreRoleRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $role = $this->route('role');

        return array_merge(parent::rules(), [
            'name' => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('roles', 'name')->ignore($role),
            ],
        ]);
    }
}
