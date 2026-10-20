<?php

namespace App\Http\Requests\Load;

use App\Enums\LoadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangeLoadStatusRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            // Cancelled is excluded on purpose: it needs a reason, so it has its
            // own endpoint (LoadCancellationService).
            'status' => ['required', Rule::enum(LoadStatus::class)->except(LoadStatus::Cancelled)],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
