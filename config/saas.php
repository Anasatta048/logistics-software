<?php

return [
    // Where "new access request" alerts go. Falls back to the first super admin.
    'notify_email' => env('SAAS_NOTIFY_EMAIL'),

    // Shown to customers whose workspace is locked or who need help.
    'support_email' => env('SAAS_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS')),

    // Where workspaces send subscription payments. A method with no value here is not offered.
    'billing' => [
        'bank_name' => env('SAAS_BANK_NAME'),
        'account_title' => env('SAAS_BANK_ACCOUNT_TITLE'),
        'account_number' => env('SAAS_BANK_ACCOUNT_NUMBER'),
        'iban' => env('SAAS_BANK_IBAN'),
        'raast_id' => env('SAAS_RAAST_ID'),
        'jazzcash_number' => env('SAAS_JAZZCASH_NUMBER'),
        'easypaisa_number' => env('SAAS_EASYPAISA_NUMBER'),
        'instructions' => env('SAAS_BILLING_INSTRUCTIONS', 'Send the exact amount, then upload the slip or screenshot here. We confirm within one business day.'),
    ],

    // Legal pages: who the customer is contracting with.
    'legal' => [
        'company' => env('SAAS_LEGAL_NAME', env('APP_NAME')),
        'address' => env('SAAS_LEGAL_ADDRESS', 'Pakistan'),
        'refund_days' => (int) env('SAAS_REFUND_DAYS', 7),
    ],

    // Equipment types every new workspace starts with.
    'starter_equipment_types' => ['Dry Van', 'Reefer', 'Flatbed', 'Power Only', 'Box Truck'],

    // Expense categories every new workspace starts with (editable afterwards).
    'starter_expense_categories' => [
        'Salaries', 'Rent', 'Electricity & utilities', 'Internet & phone', 'Load board subscriptions',
        'Software & subscriptions', 'Marketing & ads', 'Office supplies', 'Repairs & maintenance',
        'Bank & transfer charges', 'Taxes & fees', 'Travel & transport', 'Other',
    ],
];
