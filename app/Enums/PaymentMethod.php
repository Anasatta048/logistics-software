<?php

namespace App\Enums;

use App\Support\Settings\TenantSettings;
use Illuminate\Support\Str;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Cheque = 'cheque';
    case Online = 'online';
    case Zelle = 'zelle';
    case Ach = 'ach';
    case Wire = 'wire';
    case Payoneer = 'payoneer';
    case Wise = 'wise';
    case Easypaisa = 'easypaisa';
    case JazzCash = 'jazzcash';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Ach => 'ACH',
            self::JazzCash => 'JazzCash',
            self::Easypaisa => 'Easypaisa',
            default => Str::headline($this->value),
        };
    }

    /**
     * The methods this company has switched on in Business settings, plus
     * $keep (the one a record being edited already has) so an old value never
     * vanishes from its own form.
     *
     * @return list<self>
     */
    public static function enabled(self|string|null $keep = null): array
    {
        $on = TenantSettings::paymentMethods();
        $keep = $keep instanceof self ? $keep->value : $keep;

        return array_values(array_filter(
            self::cases(),
            fn (self $m): bool => in_array($m->value, $on, true) || $m->value === $keep,
        ));
    }
}
