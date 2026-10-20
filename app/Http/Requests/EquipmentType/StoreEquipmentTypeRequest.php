<?php

namespace App\Http\Requests\EquipmentType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentTypeRequest extends FormRequest
{
    /**
     * Authorization is handled by the `can:` middleware on routes/modules/equipment-types.php.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The name is the key — drivers and loads store it — so it has to be
     * unique, and case-insensitively so "Reefer" and "reefer" cannot both exist.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('equipment_types', 'name')->ignore($this->route('equipmentType'))],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'equipment type'];
    }
}
