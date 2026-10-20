<?php

namespace App\Http\Requests\Load;

use Illuminate\Foundation\Http\FormRequest;

final class CancelLoadRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            // Only meaningful when the load's commission is already inside a
            // received payment; the service rejects the request without it.
            'adjust_settlement' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Cancelling money that has already been received is an approval action,
     * not an ordinary edit — reserved for Admin.
     */
    public function authorize(): bool
    {
        return $this->boolean('adjust_settlement')
            ? $this->user()?->can('approve', \App\Models\CompanyPayment::class) === true
            : true;
    }
}
