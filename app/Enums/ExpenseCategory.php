<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum ExpenseCategory: string
{
    case Fuel = 'fuel';
    case Tolls = 'tolls';
    case RepairsMaintenance = 'repairs_maintenance';
    case Office = 'office';
    case SoftwareSubscriptions = 'software_subscriptions';
    case Salaries = 'salaries';
    case Other = 'other';

    public function label(): string
    {
        return Str::headline($this->value);
    }
}
