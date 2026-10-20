<?php

namespace App\Http\Requests\Expense;

/**
 * An expense is validated exactly the same way whether it is being posted or
 * corrected — unlike a broker, there is no unique field to ignore on update —
 * so this exists as its own class only to keep the module's naming symmetric.
 */
class UpdateExpenseRequest extends StoreExpenseRequest
{
}
