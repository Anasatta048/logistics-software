<?php

namespace App\Policies;

use App\Models\EquipmentType;
use App\Models\User;

final class EquipmentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('equipment-types.view');
    }

    /**
     * The list is master data, not money: anyone who books a load needs to
     * read it, and the forms gate the edit buttons on the same permission.
     */
    public function create(User $user): bool
    {
        return $user->can('equipment-types.create');
    }

    public function update(User $user, EquipmentType $equipmentType): bool
    {
        return $user->can('equipment-types.edit');
    }

    public function delete(User $user, EquipmentType $equipmentType): bool
    {
        return $user->can('equipment-types.delete');
    }
}
