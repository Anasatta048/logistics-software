<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** One extra field a company added to loads, drivers or carrier companies. */
class CustomField extends Model
{
    use BelongsToTenant;

    public const ENTITIES = ['load' => 'Loads', 'driver' => 'Drivers', 'company' => 'Carrier companies'];

    public const TYPES = ['text' => 'Text', 'number' => 'Number', 'date' => 'Date', 'select' => 'Dropdown', 'checkbox' => 'Yes / No'];

    protected $fillable = ['entity', 'key', 'label', 'type', 'options', 'is_required', 'show_in_list', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'show_in_list' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
