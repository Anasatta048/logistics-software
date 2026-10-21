<?php

namespace App\Http\Requests\Billing;

use App\Services\Tenancy\SubscriptionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SubmitPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-billing') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', Rule::exists('plans', 'id')->where('is_active', true)],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'method' => ['required', Rule::in(array_keys(SubscriptionService::methods()))],
            'reference' => ['nullable', 'string', 'max:120'],
            // The slip is what lets the platform owner match the money to the workspace.
            'proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
