<?php

namespace App\Http\Requests\Driver;

final class UpdateDriverRequest extends StoreDriverRequest
{
    // ponytail: the driver form submits every field, so the rules are identical
    // to Store. Split them if partial PATCH updates ever land.
}
