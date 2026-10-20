<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-saas') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash', Rule::unique('plans', 'slug')->ignore($this->route('plan'))],
            'tagline' => ['nullable', 'string', 'max:190'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:99999'],
            'yearly_price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_drivers' => ['nullable', 'integer', 'min:1'],
            'max_loads_per_month' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'string', 'max:3000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65000'],
        ];
    }

    /** @return array<string, mixed> */
    public function planData(): array
    {
        $data = $this->validated();

        $data['slug'] = Str::slug($data['slug'] ?? '') ?: Str::slug($data['name']);
        $data['trial_days'] = (int) ($data['trial_days'] ?? 0);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        // One feature per line.
        $data['features'] = collect(preg_split('/\R/', (string) ($data['features'] ?? '')))
            ->map(fn ($line) => trim($line))->filter()->values()->all();
        $data['is_featured'] = $this->boolean('is_featured');
        $data['is_active'] = $this->boolean('is_active');

        return $data;
    }
}
