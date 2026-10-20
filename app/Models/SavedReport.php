<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A report with its filters kept under a name, and optionally mailed on a schedule. */
class SavedReport extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'report', 'name', 'filters', 'schedule', 'schedule_day', 'format', 'recipients', 'last_sent_at'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'last_sent_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return list<string> */
    public function recipientList(): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $this->recipients))->filter()->unique()->values()->all();
    }

    public function scheduleLabel(): string
    {
        return match ($this->schedule) {
            'weekly' => 'Every '.['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$this->schedule_day] ?? 'week',
            'monthly' => 'Monthly on day '.$this->schedule_day,
            default => 'Not scheduled',
        };
    }
}
