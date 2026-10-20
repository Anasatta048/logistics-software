<?php

namespace App\Http\Requests\EquipmentType;

final class UpdateEquipmentTypeRequest extends StoreEquipmentTypeRequest
{
    // ponytail: one form posts name and nothing else, so the rules are the same.
    // Split when a type ever grows its own fields.
}
