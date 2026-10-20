<?php

use App\Models\Account;
use App\Models\Broker;
use App\Models\Commission;
use App\Models\CommissionPayout;
use App\Models\Company;
use App\Models\CompanyPayment;
use App\Models\Driver;
use App\Models\EquipmentType;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalVoucher;
use App\Models\Load;
use App\Models\LoadAttachment;
use App\Models\Setting;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Every model listed here is audited by ActivityObserver: created / updated /
 * deleted / restored, with a readable before→after for edits.
 *
 *   module  the heading the Activity Log groups it under
 *   noun    how the description names it ("Updated load DEL-000012")
 *   label   the column that identifies the record to a human
 *   prefix  optional text put in front of the label (e.g. "#" for numbers)
 */
return [
    /** Master switch (ACTIVITY_LOG_ENABLED=false turns model auditing off). */
    'enabled' => env('ACTIVITY_LOG_ENABLED', true),

    'models' => [
        Load::class => ['module' => 'Loads', 'noun' => 'load', 'label' => 'reference_number'],
        LoadAttachment::class => ['module' => 'Loads', 'noun' => 'load document', 'label' => 'file_path'],
        Company::class => ['module' => 'Companies', 'noun' => 'company', 'label' => 'name'],
        Driver::class => ['module' => 'Drivers', 'noun' => 'driver', 'label' => 'name'],
        Broker::class => ['module' => 'Brokers', 'noun' => 'broker', 'label' => 'company_name'],
        EquipmentType::class => ['module' => 'Equipment Types', 'noun' => 'equipment type', 'label' => 'name'],
        Invoice::class => ['module' => 'Invoices', 'noun' => 'invoice', 'label' => 'invoice_number', 'prefix' => '#'],
        CompanyPayment::class => ['module' => 'Payment Receipts', 'noun' => 'payment receipt', 'label' => 'id', 'prefix' => '#'],
        Expense::class => ['module' => 'Expenses', 'noun' => 'expense', 'label' => 'id', 'prefix' => '#'],
        Account::class => ['module' => 'Accounts', 'noun' => 'account', 'label' => 'name'],
        JournalVoucher::class => ['module' => 'Journal Vouchers', 'noun' => 'journal voucher', 'label' => 'jv_number'],
        Commission::class => ['module' => 'Commissions', 'noun' => 'commission', 'label' => 'id', 'prefix' => '#'],
        CommissionPayout::class => ['module' => 'Commissions', 'noun' => 'commission payout', 'label' => 'id', 'prefix' => '#'],
        User::class => ['module' => 'Users', 'noun' => 'user', 'label' => 'name'],
        Role::class => ['module' => 'Roles & Rights', 'noun' => 'role', 'label' => 'name'],
        Setting::class => ['module' => 'Settings', 'noun' => 'setting', 'label' => 'key'],
    ],

    /** Never written to a log: noise, secrets, or derived on every save. */
    'ignore' => [
        'id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token',
        'email_verified_at', 'notification_preferences', 'guard_name',
    ],

    /** Module used for sign-in / sign-out rows. */
    'auth_module' => 'Authentication',
];
