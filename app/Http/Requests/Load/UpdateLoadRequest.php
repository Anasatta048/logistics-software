<?php

namespace App\Http\Requests\Load;

final class UpdateLoadRequest extends StoreLoadRequest
{
    // ponytail: the load form submits every field, so the rules are identical.
    // Split the two when Phase 4's map starts auto-filling fields that are then
    // submitted on their own.
}
