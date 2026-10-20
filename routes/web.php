<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file intentionally contains `require` statements only. Every module
| owns its own route file under routes/modules/ (see Section 0.2 of
| LARAVEL_DEV_PLAN.md). Never register a route directly here.
|
*/

require __DIR__.'/modules/landing.php';
require __DIR__.'/modules/auth.php';
require __DIR__.'/modules/super-admin.php';
require __DIR__.'/modules/users.php';
require __DIR__.'/modules/roles.php';
require __DIR__.'/modules/companies.php';
require __DIR__.'/modules/drivers.php';
require __DIR__.'/modules/brokers.php';
require __DIR__.'/modules/equipment-types.php';
require __DIR__.'/modules/loads.php';
require __DIR__.'/modules/commissions.php';
require __DIR__.'/modules/company-payments.php';
require __DIR__.'/modules/invoices.php';
require __DIR__.'/modules/expenses.php';
require __DIR__.'/modules/expense-categories.php';
require __DIR__.'/modules/accounts.php';
require __DIR__.'/modules/account-transfers.php';
require __DIR__.'/modules/journal-vouchers.php';
require __DIR__.'/modules/reports.php';
require __DIR__.'/modules/saved-reports.php';
require __DIR__.'/modules/notifications.php';
require __DIR__.'/modules/settings.php';
require __DIR__.'/modules/custom-fields.php';
require __DIR__.'/modules/money-settings.php';
require __DIR__.'/modules/data.php';
require __DIR__.'/modules/activity-logs.php';
require __DIR__.'/modules/maps.php';
