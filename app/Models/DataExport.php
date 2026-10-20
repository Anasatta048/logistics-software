<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** One requested full-data backup (a ZIP of CSV files). */
class DataExport extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'status', 'path', 'size', 'error'];
}
