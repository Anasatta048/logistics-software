<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What the platform owner did (approvals, suspensions, plan changes, entering a
 * workspace). Not tenant-scoped: it is the platform's own record.
 */
class PlatformAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'tenant_id', 'action', 'description', 'meta', 'ip_address'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param array<string, mixed> $meta */
    public static function record(string $action, ?int $tenantId = null, ?string $description = null, array $meta = []): void
    {
        static::query()->create([
            'user_id' => auth()->id(),
            'tenant_id' => $tenantId,
            'action' => $action,
            'description' => $description,
            'meta' => $meta ?: null,
            'ip_address' => request()->ip(),
        ]);
    }
}
