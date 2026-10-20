<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared filter shape for every report: date range plus optional
 * user/broker/driver/account scoping. Authorization is handled by the named
 * `reports.{report}` Gate on each route (see routes/modules/reports.php); the
 * dispatcher-scoping override itself happens in ReportService, never trusting
 * `user_id` here for a non-privileged role.
 */
final class ReportFilterRequest extends FormRequest
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
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer', \App\Support\Tenancy\TenantRule::exists('users', 'id')],
            'broker_id' => ['nullable', 'integer', \App\Support\Tenancy\TenantRule::exists('brokers', 'id')],
            'driver_id' => ['nullable', 'integer', \App\Support\Tenancy\TenantRule::exists('drivers', 'id')],
            'company_id' => ['nullable', 'integer', \App\Support\Tenancy\TenantRule::exists('companies', 'id')],
            'account_id' => ['nullable', 'integer', \App\Support\Tenancy\TenantRule::exists('accounts', 'id')],
            'expense_category_id' => ['nullable', 'integer', \App\Support\Tenancy\TenantRule::exists('expense_categories', 'id')],
            'section' => ['nullable', 'in:category,month,account,method,payee'],
            'status' => ['nullable', 'string'],
            'export' => ['nullable', 'in:csv,pdf'],
        ];
    }

    public function wantsExport(): bool
    {
        return in_array($this->validated('export'), ['csv', 'pdf'], true);
    }
}
