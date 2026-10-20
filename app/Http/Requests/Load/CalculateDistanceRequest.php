<?php

namespace App\Http\Requests\Load;

use Illuminate\Foundation\Http\FormRequest;

final class CalculateDistanceRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'origin_lat' => ['required', 'numeric', 'between:-90,90'],
            'origin_lng' => ['required', 'numeric', 'between:-180,180'],
            'destination_lat' => ['required', 'numeric', 'between:-90,90'],
            'destination_lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function authorize(): bool
    {
        // Same permission as working a load: whoever may book loads may measure them.
        return $this->user()?->can('loads.create') === true
            || $this->user()?->can('loads.edit') === true;
    }
}
