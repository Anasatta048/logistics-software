<?php

/*
|--------------------------------------------------------------------------
| Tenant settings registry
|--------------------------------------------------------------------------
|
| Every business setting is declared here once: the settings screen is drawn
| from it, input is validated against it and defaults come from it. Adding a
| setting is one entry in this file, with no new controller, view or migration.
| Values are stored per tenant, one JSON row per group (see TenantSettings).
|
| field keys: type text|textarea|email|number|select|multiselect|color,
| label, hint, default, min, max, step, options (select), required.
|
*/

return [
    'groups' => [
        'business' => [
            'label' => 'Business profile',
            'icon' => 'building',
            'description' => 'Printed on every invoice and used across the app.',
            'fields' => [
                'name' => ['type' => 'text', 'label' => 'Business name', 'required' => true, 'max' => 120],
                'email' => ['type' => 'email', 'label' => 'Billing email', 'max' => 120],
                'phone' => ['type' => 'text', 'label' => 'Phone', 'max' => 40],
                'website' => ['type' => 'text', 'label' => 'Website', 'max' => 120],
                'address' => ['type' => 'textarea', 'label' => 'Address', 'max' => 300],
                'tax_id' => ['type' => 'text', 'label' => 'Tax / registration no.', 'hint' => 'NTN, EIN or similar. Shown on invoices when filled.', 'max' => 60],
            ],
        ],

        'finance' => [
            'label' => 'Finance & invoicing',
            'icon' => 'wallet',
            'description' => 'Defaults that save typing when you bill carriers and record money.',
            'fields' => [
                'invoice_due_days' => ['type' => 'number', 'label' => 'Invoice due after (days)', 'default' => 7, 'min' => 0, 'max' => 120],
                'invoice_start_number' => ['type' => 'number', 'label' => 'First invoice number', 'default' => 1001, 'min' => 1, 'max' => 99999999, 'hint' => 'Used only until your first invoice exists; numbers then continue from the last one.'],
                'invoice_color' => ['type' => 'color', 'label' => 'Invoice accent colour', 'default' => '#E8622C'],
                'invoice_note' => ['type' => 'textarea', 'label' => 'Invoice footer note', 'hint' => 'e.g. bank details or payment terms printed under every invoice.', 'max' => 500],
                'default_fee_percentage' => ['type' => 'number', 'label' => 'Default service fee % (new carrier companies)', 'default' => 10, 'min' => 0, 'max' => 100, 'step' => 0.01],
                'default_commission_percentage' => ['type' => 'number', 'label' => 'Default dispatcher commission %', 'default' => 10, 'min' => 0, 'max' => 100, 'step' => 0.01],
                'fallback_usd_rate' => ['type' => 'number', 'label' => 'Fallback USD → PKR rate', 'default' => 280, 'min' => 1, 'max' => 100000, 'step' => 0.01, 'hint' => 'Suggested when there is no earlier conversion to copy the rate from.'],
                'default_tax_type' => ['type' => 'select', 'label' => 'Default tax type', 'default' => 'percentage', 'options' => 'tax_types'],
                'default_tax_value' => ['type' => 'number', 'label' => 'Default tax value', 'default' => 0, 'min' => 0, 'max' => 1000000, 'step' => 0.01],
                'payment_methods' => ['type' => 'multiselect', 'label' => 'Payment methods in use', 'options' => 'payment_methods', 'hint' => 'Only these appear on payment forms. Records already saved keep theirs.'],
            ],
        ],

        'operations' => [
            'label' => 'Operations',
            'icon' => 'truck',
            'description' => 'How loads and the dashboard behave.',
            'fields' => [
                'overdue_pickup_grace_hours' => ['type' => 'number', 'label' => 'Flag a late pickup after (hours)', 'default' => 0, 'min' => 0, 'max' => 72, 'hint' => 'A booked load shows under "Needs attention" this long after its pickup time.'],
                'low_balance_alert' => ['type' => 'number', 'label' => 'Low balance alert (PKR)', 'default' => 0, 'min' => 0, 'max' => 1000000000, 'hint' => '0 turns it off. Any active account below this shows on the dashboard.'],
                'invoice_overdue_alert_days' => ['type' => 'number', 'label' => 'Invoice overdue alert after (days)', 'default' => 0, 'min' => 0, 'max' => 365, 'hint' => '0 uses the due date as it is.'],
            ],
        ],
    ],
];
