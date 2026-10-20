<?php

namespace App\Http\Requests\Invoice;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Money received against an invoice. Authorization is the
 * `can:create,CompanyPayment` middleware on routes/modules/invoices.php.
 */
final class RecordInvoicePaymentRequest extends FormRequest
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
            'amount_received' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
