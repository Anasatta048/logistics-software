<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Commission;
use App\Models\CompanyPayment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\JournalVoucher;
use App\Models\Role;

/**
 * The switchable parts of the product. A plan lists the modules it includes
 * (plans.modules); everything not listed here (loads, companies, drivers,
 * brokers, equipment, invoices, users, settings) is core and in every plan.
 */
final class PlanModules
{
    /** @var array<string, array{label: string, hint: string}> */
    public const ALL = [
        'reports' => ['label' => 'Basic reports', 'hint' => 'Load, invoice and receipt reports; saved reports.'],
        'advanced_reports' => ['label' => 'Advanced reports', 'hint' => 'Profit & loss, account statement, funds flow.'],
        'accounts' => ['label' => 'Accounts & transfers', 'hint' => 'Cash/bank accounts and transfers between them.'],
        'expenses' => ['label' => 'Expenses', 'hint' => 'Company costs and expense categories.'],
        'journal_vouchers' => ['label' => 'Journal vouchers', 'hint' => 'Manual ledger postings.'],
        'company_payments' => ['label' => 'Company payments', 'hint' => 'Weekly receipts and receivables.'],
        'commissions' => ['label' => 'Commissions', 'hint' => 'Dispatcher commissions and payouts.'],
        'roles' => ['label' => 'Roles & rights', 'hint' => 'Custom roles and permissions.'],
        'data' => ['label' => 'Import & export', 'hint' => 'Spreadsheet import and full backup export.'],
        'activity_log' => ['label' => 'Activity log', 'hint' => 'Audit trail of everyone\'s actions.'],
    ];

    /** Models whose policies belong to a module. */
    private const MODELS = [
        Account::class => 'accounts',
        Expense::class => 'expenses',
        ExpenseCategory::class => 'expenses',
        JournalVoucher::class => 'journal_vouchers',
        CompanyPayment::class => 'company_payments',
        Commission::class => 'commissions',
        Role::class => 'roles',
    ];

    /** Permission / gate name prefix => module. */
    private const PREFIXES = [
        'accounts' => 'accounts',
        'expenses' => 'expenses',
        'expense-categories' => 'expenses',
        'journal-vouchers' => 'journal_vouchers',
        'company-payments' => 'company_payments',
        'commissions' => 'commissions',
        'roles' => 'roles',
        'data' => 'data',
        'activity-logs' => 'activity_log',
        'reports' => 'reports',
    ];

    private const ABILITIES = [
        'view-company-commission' => 'commissions',
        'view-activity-logs' => 'activity_log',
        'manage-data' => 'data',
        'reports.profitAndLoss' => 'advanced_reports',
        'reports.accountStatement' => 'advanced_reports',
        'reports.fundsFlow' => 'advanced_reports',
    ];

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::ALL);
    }

    /** The module an authorization check belongs to, or null for core features. */
    public static function forAbility(string $ability, array $arguments = []): ?string
    {
        if (isset(self::ABILITIES[$ability])) {
            return self::ABILITIES[$ability];
        }

        $subject = $arguments[0] ?? null;
        $class = is_object($subject) ? $subject::class : (is_string($subject) ? $subject : null);

        if ($class !== null && isset(self::MODELS[$class])) {
            return self::MODELS[$class];
        }

        return str_contains($ability, '.') ? (self::PREFIXES[explode('.', $ability)[0]] ?? null) : null;
    }
}
