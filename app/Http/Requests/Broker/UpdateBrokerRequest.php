<?php

namespace App\Http\Requests\Broker;

use App\Enums\BrokerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrokerRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:` middleware on routes/modules/brokers.php.
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
            'company_name' => ['required', 'string', 'max:255'],
            'broker_mc_number' => [
                'required', 'string', 'max:32',
                Rule::unique('brokers', 'broker_mc_number')->ignore($this->route('broker')),
            ],
            'credit_notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(BrokerStatus::class)],
        ];
    }
}
