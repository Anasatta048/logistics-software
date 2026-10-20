<?php

namespace App\Http\Requests\Company;

use Illuminate\Validation\Rule;

final class UpdateCompanyRequest extends StoreCompanyRequest
{
    /**
     * The MC number is unique company-wide, but must ignore this company's own
     * row or every edit of anything else would trip the unique rule.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'mc_number' => [
                'required',
                'string',
                'max:32',
                \App\Support\Tenancy\TenantRule::unique('companies', 'mc_number')->ignore($this->route('company')),
            ],
        ]);
    }
}
