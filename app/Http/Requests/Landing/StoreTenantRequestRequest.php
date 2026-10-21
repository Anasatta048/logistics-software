<?php

namespace App\Http\Requests\Landing;

use App\Enums\RequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['email.unique' => 'We already have a pending request from this email. We will be in touch shortly.'];
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'contact_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('tenant_requests', 'email')->where('status', RequestStatus::Pending->value)],
            'phone' => ['nullable', 'string', 'max:40'],
            'fleet_size' => ['nullable', Rule::in(['1-5', '6-20', '21-50', '51-200', '200+'])],
            'plan_id' => ['nullable', Rule::exists('plans', 'id')->where('is_active', true)],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
