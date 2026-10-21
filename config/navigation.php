<?php

/**
 * UI_UX_DESIGN_PLAN.md Section 2.2 — the nav items are defined exactly once
 * here (icon, label, route, permission gate) and the Sidebar, Topbar and mobile
 * drawer all render this same list, so they never drift apart.
 *
 * `gate` is either null (always visible to an authenticated user), an
 * ability string passed to Gate::allows(), or an array [ability, modelClass]
 * passed to Gate::allows(ability, modelClass).
 *
 * An item may carry `children` (the sidebar's submenu: usually "View all" and
 * "Add new", so a form is one click away). Each child has its own optional gate;
 * a child shows only if the parent does AND its own gate passes.
 */

use App\Models\Account;
use App\Models\Broker;
use App\Models\Commission;
use App\Models\Company;
use App\Models\CompanyPayment;
use App\Models\Driver;
use App\Models\EquipmentType;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalVoucher;
use App\Models\Load;
use App\Models\User;

return [
    'groups' => [
        [
            'label' => null,
            'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'dashboard', 'gate' => null],
            ],
        ],
        [
            'label' => 'Platform',
            'items' => [
                ['label' => 'SaaS Overview', 'route' => 'super-admin.dashboard', 'icon' => 'layers', 'gate' => 'manage-saas'],
                ['label' => 'Plans', 'route' => 'super-admin.plans.index', 'icon' => 'tag', 'gate' => 'manage-saas', 'children' => [
                    ['label' => 'All plans', 'route' => 'super-admin.plans.index'],
                    ['label' => 'Add plan', 'route' => 'super-admin.plans.create'],
                ]],
                ['label' => 'Payments', 'route' => 'super-admin.payments.index', 'icon' => 'wallet', 'gate' => 'manage-saas'],
                ['label' => 'Workspaces', 'route' => 'super-admin.tenants.index', 'icon' => 'building', 'gate' => 'manage-saas'],
                ['label' => 'Access Requests', 'route' => 'super-admin.requests.index', 'icon' => 'inbox', 'gate' => 'manage-saas'],
            ],
        ],
        [
            'label' => 'Operations',
            'items' => [
                ['label' => 'Loads', 'route' => 'loads.index', 'icon' => 'truck', 'gate' => ['viewAny', Load::class], 'children' => [
                    ['label' => 'All loads', 'route' => 'loads.index'],
                    ['label' => 'Add load', 'route' => 'loads.create', 'gate' => ['create', Load::class]],
                ]],
                ['label' => 'Companies', 'route' => 'companies.index', 'icon' => 'building', 'gate' => ['viewAny', Company::class], 'children' => [
                    ['label' => 'All companies', 'route' => 'companies.index'],
                    ['label' => 'Add company', 'route' => 'companies.create', 'gate' => ['create', Company::class]],
                ]],
                ['label' => 'Drivers', 'route' => 'drivers.index', 'icon' => 'users', 'gate' => ['viewAny', Driver::class], 'children' => [
                    ['label' => 'All drivers', 'route' => 'drivers.index'],
                    ['label' => 'Add driver', 'route' => 'drivers.create', 'gate' => ['create', Driver::class]],
                ]],
                ['label' => 'Brokers', 'route' => 'brokers.index', 'icon' => 'building', 'gate' => ['viewAny', Broker::class], 'children' => [
                    ['label' => 'All brokers', 'route' => 'brokers.index'],
                    ['label' => 'Add broker', 'route' => 'brokers.create', 'gate' => ['create', Broker::class]],
                ]],
                ['label' => 'Equipment Types', 'route' => 'equipment-types.index', 'icon' => 'truck', 'gate' => ['viewAny', EquipmentType::class], 'children' => [
                    ['label' => 'All types', 'route' => 'equipment-types.index'],
                    ['label' => 'Add type', 'route' => 'equipment-types.create', 'gate' => ['create', EquipmentType::class]],
                ]],
            ],
        ],
        [
            'label' => 'Accounts',
            'items' => [
                ['label' => 'Company Payments', 'route' => 'invoices.index', 'icon' => 'file-text', 'gate' => ['viewAny', Invoice::class], 'children' => [
                    ['label' => 'All invoices', 'route' => 'invoices.index'],
                    ['label' => 'New invoice', 'route' => 'invoices.create', 'gate' => ['create', Invoice::class]],
                ]],
                ['label' => 'Payment Receipts', 'route' => 'company-payments.index', 'icon' => 'wallet', 'gate' => ['viewAny', CompanyPayment::class]],
                ['label' => 'Expenses', 'route' => 'expenses.index', 'icon' => 'receipt', 'gate' => ['viewAny', Expense::class], 'children' => [
                    ['label' => 'All expenses', 'route' => 'expenses.index'],
                    ['label' => 'Add expense', 'route' => 'expenses.create', 'gate' => ['create', Expense::class]],
                ]],
                ['label' => 'Accounts', 'route' => 'accounts.index', 'icon' => 'wallet', 'gate' => ['viewAny', Account::class], 'children' => [
                    ['label' => 'All accounts', 'route' => 'accounts.index'],
                    ['label' => 'Add account', 'route' => 'accounts.create', 'gate' => ['create', Account::class]],
                ]],
                ['label' => 'Transfers', 'route' => 'account-transfers.index', 'icon' => 'repeat', 'gate' => 'accounts.view', 'children' => [
                    ['label' => 'All transfers', 'route' => 'account-transfers.index'],
                    ['label' => 'New transfer', 'route' => 'account-transfers.create', 'gate' => 'accounts.transfer'],
                ]],
                ['label' => 'Journal Vouchers', 'route' => 'journal-vouchers.index', 'icon' => 'file-text', 'gate' => ['viewAny', JournalVoucher::class], 'children' => [
                    ['label' => 'All vouchers', 'route' => 'journal-vouchers.index'],
                    ['label' => 'Add voucher', 'route' => 'journal-vouchers.create', 'gate' => ['create', JournalVoucher::class]],
                ]],
                ['label' => 'Commissions', 'route' => 'commissions.index', 'icon' => 'chart-bar', 'gate' => ['viewAny', Commission::class]],
            ],
        ],
        [
            'label' => 'Reports',
            'items' => [
                ['label' => 'Reports', 'route' => 'reports.index', 'icon' => 'chart-bar', 'gate' => 'reports.viewAny', 'children' => [
                    ['label' => 'All reports', 'route' => 'reports.index'],
                    ['label' => 'Saved reports', 'route' => 'saved-reports.index'],
                ]],
            ],
        ],
        [
            'label' => 'Admin',
            'items' => [
                ['label' => 'Users', 'route' => 'users.index', 'icon' => 'shield', 'gate' => ['viewAny', User::class], 'children' => [
                    ['label' => 'All users', 'route' => 'users.index'],
                    ['label' => 'Add user', 'route' => 'users.create', 'gate' => ['create', User::class]],
                ]],
                ['label' => 'Roles & Rights', 'route' => 'roles.index', 'icon' => 'key', 'gate' => ['viewAny', User::class], 'module' => 'roles', 'children' => [
                    ['label' => 'All roles', 'route' => 'roles.index'],
                    ['label' => 'Add role', 'route' => 'roles.create', 'gate' => ['create', App\Models\Role::class]],
                ]],
                ['label' => 'Billing', 'route' => 'billing.index', 'icon' => 'wallet', 'gate' => 'manage-billing'],
                ['label' => 'Business Settings', 'route' => 'settings.business.edit', 'icon' => 'building', 'gate' => 'manage-settings'],
                ['label' => 'Import & Export', 'route' => 'data.index', 'icon' => 'upload', 'gate' => 'manage-data'],
                ['label' => 'My Settings', 'route' => 'profile.edit', 'icon' => 'settings', 'gate' => null],
                ['label' => 'Activity Log', 'route' => 'activity-logs.index', 'icon' => 'file-text', 'gate' => 'view-activity-logs'],
            ],
        ],
    ],
];
