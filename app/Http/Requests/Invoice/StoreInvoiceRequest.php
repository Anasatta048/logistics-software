<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

final class StoreInvoiceRequest extends FormRequest
{
    /**
     * Authorization is the `can:create` middleware on routes/modules/invoices.php.
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
            'driver_id' => ['required', 'integer', \App\Support\Tenancy\TenantRule::exists('drivers', 'id')->whereNull('deleted_at')],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            'load_ids' => ['required', 'array', 'min:1'],
            'load_ids.*' => ['integer', 'distinct', \App\Support\Tenancy\TenantRule::exists('loads', 'id')],
            'include_previous' => ['nullable', 'boolean'],
            'use_credit' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'load_ids.required' => 'Tick at least one load to invoice.',
            'load_ids.min' => 'Tick at least one load to invoice.',
            'due_date.after_or_equal' => 'The due date cannot be before the invoice date.',
            'period_to.after_or_equal' => 'The period end cannot be before the period start.',
        ];
    }
}
