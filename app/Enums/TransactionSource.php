<?php

namespace App\Enums;

/**
 * What caused a ledger movement. Stored as a plain string rather than a morph
 * relation because 'manual' has no model to point at.
 */
enum TransactionSource: string
{
    case Expense = 'expense';
    case JournalVoucher = 'journal_voucher';
    case CurrencyConversion = 'currency_conversion';
    case Commission = 'commission';
    case Transfer = 'transfer';
    case Manual = 'manual';
}
