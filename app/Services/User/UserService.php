<?php

namespace App\Services\User;

use App\Enums\CommissionType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class UserService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create($this->withoutNullCommission(Arr::except($data, ['role', 'companies'])));
            $this->assignRole($user, $data['role']);
            $this->syncCompanies($user, $data);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $attributes = $this->withoutNullCommission(Arr::except($data, ['role', 'companies']));

            // Blank password on the edit form means "keep the current one".
            if (blank($attributes['password'] ?? null)) {
                unset($attributes['password']);
            }

            $user->update($attributes);
            $this->assignRole($user, $data['role']);
            $this->syncCompanies($user, $data);

            return $user->refresh();
        });
    }

    /**
     * Blank commission inputs arrive as null, which would be written over the
     * column's NOT NULL default. Leaving the key out keeps the existing value.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withoutNullCommission(array $attributes): array
    {
        foreach (['commission_type', 'commission_percentage', 'commission_fixed_amount'] as $key) {
            if (array_key_exists($key, $attributes) && $attributes[$key] === null) {
                unset($attributes[$key]);
            }
        }

        return $attributes;
    }

    /**
     * Only dispatchers are restricted to companies, so other roles keep none.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncCompanies(User $user, array $data): void
    {
        $user->companies()->sync($data['role'] === 'dispatcher' ? ($data['companies'] ?? []) : []);
    }

    public function assignRole(User $user, string $role): User
    {
        $before = $user->roles->pluck('name')->implode(', ');
        $user->syncRoles([$role]);

        if ($before !== $role) {
            app(ActivityLogger::class)->log(
                'Users',
                'updated',
                $before === ''
                    ? "Assigned role {$role} to user {$user->name}"
                    : "Changed role of user {$user->name}: {$before} → {$role}",
                $user,
                $user->name,
                [['field' => 'Role', 'old' => $before ?: '—', 'new' => $role]],
            );
        }

        return $user;
    }

    public function setCommissionPercentage(User $user, float $percentage): User
    {
        $user->update(['commission_type' => CommissionType::Percentage, 'commission_percentage' => $percentage]);

        return $user;
    }

    public function deactivate(User $user): User
    {
        $user->update(['status' => UserStatus::Inactive]);

        return $user;
    }

    public function activate(User $user): User
    {
        $user->update(['status' => UserStatus::Active]);

        return $user;
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(int $perPage = 500): LengthAwarePaginator
    {
        return User::with('roles')->latest()->paginate($perPage);
    }

    /**
     * @return Collection<int, string>
     */
    public function roles(): Collection
    {
        return Role::query()->orderBy('name')->pluck('name');
    }
}
