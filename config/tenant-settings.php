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
                'invoice_note' => ['type' => 'textarea', 'label' => 'Invoice footer note', 'hint' => 'e.g. bank details or payment terms printed under every invoice.', 'max' => 500],
                'default_fee_percentage' => ['type' => 'number', 'label' => 'Default service fee % (new carrier companies)', 'default' => 10, 'min' => 0, 'max' => 100, 'step' => 0.01],
                'commission_mode' => ['type' => 'select', 'label' => 'Dispatcher commission', 'default' => 'flat', 'options' => 'commission_modes', 'hint' => 'Slab mode uses the ladder under Commission slabs instead of each dispatcher\'s own %. Fixed-amount dispatchers are unaffected.'],
                'default_commission_percentage' => ['type' => 'number', 'label' => 'Default dispatcher commission %', 'default' => 10, 'min' => 0, 'max' => 100, 'step' => 0.01],
                'fallback_usd_rate' => ['type' => 'number', 'label' => 'Fallback USD → PKR rate', 'default' => 280, 'min' => 1, 'max' => 100000, 'step' => 0.01, 'hint' => 'Suggested when there is no earlier conversion to copy the rate from.'],
                'payment_methods' => ['type' => 'multiselect', 'label' => 'Payment methods in use', 'options' => 'payment_methods', 'hint' => 'Only these appear on payment forms. Records already saved keep theirs.'],
            ],
        ],

        'branding' => [
            'label' => 'Branding',
            'icon' => 'palette',
            'description' => 'Your logo and colours, used across the app, invoices and report PDFs.',
            'fields' => [
                'logo' => ['type' => 'image', 'label' => 'Logo', 'hint' => 'PNG, JPG or WebP up to 1 MB. Shown in the sidebar, on invoices and on report PDFs.'],
                'accent_color' => ['type' => 'color', 'label' => 'Accent colour', 'default' => '#E8622C', 'hint' => 'Buttons, highlights, charts and invoice headings.'],
                'sidebar_color' => ['type' => 'color', 'label' => 'Sidebar and header colour', 'default' => '#1B2A4A'],
                'invoice_template' => ['type' => 'select', 'label' => 'Invoice layout', 'default' => 'classic', 'options' => 'invoice_templates'],
            ],
        ],

        'loadboards' => [
            'label' => 'Load boards',
            'icon' => 'truck',
            'description' => 'Shortcuts to the load boards your dispatchers use. They appear under "Load boards" in the top bar and open in a new tab.',
            'fields' => [
                'links' => ['type' => 'textarea', 'label' => 'Links', 'default' => \App\Support\LoadBoards::DEFAULT, 'max' => 1200, 'hint' => 'One per line: Name | https://address. Only http(s) links are shown.'],
            ],
        ],

        'numbering' => [
            'label' => 'Numbering',
            'icon' => 'hash',
            'description' => 'How load, voucher and invoice numbers look. Numbers are per company and never repeat.',
            'fields' => [
                'load_prefix' => ['type' => 'text', 'label' => 'Load number prefix', 'default' => 'LD', 'max' => 12, 'hint' => 'A blank load number is filled in as PREFIX-0001, PREFIX-0002...'],
                'load_padding' => ['type' => 'number', 'label' => 'Load number digits', 'default' => 4, 'min' => 1, 'max' => 10],
                'load_start_number' => ['type' => 'number', 'label' => 'First load number', 'default' => 1, 'min' => 1, 'max' => 99999999, 'hint' => 'Only matters before your first generated number.'],
                'invoice_prefix' => ['type' => 'text', 'label' => 'Invoice prefix', 'default' => '', 'max' => 12, 'hint' => 'Shown before the invoice number, e.g. INV- gives INV-1001. Leave blank for plain numbers.'],
                'jv_prefix' => ['type' => 'text', 'label' => 'Journal voucher prefix', 'default' => 'JV', 'max' => 12],
                'jv_padding' => ['type' => 'number', 'label' => 'Journal voucher digits', 'default' => 6, 'min' => 1, 'max' => 10],
            ],
        ],

        'regional' => [
            'label' => 'Regional',
            'icon' => 'globe',
            'description' => 'Dates, week and money labels in the way your team reads them.',
            'fields' => [
                'date_format' => ['type' => 'select', 'label' => 'Date format', 'default' => 'M j, Y', 'options' => 'date_formats'],
                'week_start' => ['type' => 'select', 'label' => 'Week starts on', 'default' => 'monday', 'options' => 'week_starts'],
                'fiscal_year_start' => ['type' => 'select', 'label' => 'Financial year starts in', 'default' => '7', 'options' => 'months', 'hint' => 'Pakistan runs July to June.'],
                'local_currency' => ['type' => 'text', 'label' => 'Local currency symbol', 'default' => 'Rs', 'max' => 8],
                'foreign_currency' => ['type' => 'text', 'label' => 'Client currency symbol', 'default' => '$', 'max' => 8],
            ],
        ],

        'loads' => [
            'label' => 'Load rules',
            'icon' => 'truck',
            'description' => 'Which details a dispatcher must fill in before a load can be saved.',
            'fields' => [
                'require_driver' => ['type' => 'toggle', 'label' => 'Require a driver', 'default' => false],
                'require_broker_contact' => ['type' => 'toggle', 'label' => 'Require broker contact person and phone', 'default' => false],
                'require_weight' => ['type' => 'toggle', 'label' => 'Require weight', 'default' => true],
                'require_dimensions' => ['type' => 'toggle', 'label' => 'Require length, width and height', 'default' => false],
                'require_notes' => ['type' => 'toggle', 'label' => 'Require notes', 'default' => false],
            ],
        ],

        'dashboard' => [
            'label' => 'Dashboard',
            'icon' => 'dashboard',
            'description' => 'What the home screen shows for everyone in your company.',
            'fields' => [
                'widgets' => ['type' => 'multiselect', 'label' => 'Sections to show', 'options' => 'dashboard_widgets'],
                'chart_days' => ['type' => 'select', 'label' => 'Revenue chart covers', 'default' => '14', 'options' => 'chart_ranges'],
            ],
        ],

        'notifications' => [
            'label' => 'Notifications',
            'icon' => 'bell',
            'description' => 'Company-wide: which events also send an email, and what the email says. People still choose their own in-app alerts under My Settings.',
            'fields' => [
                'email_events' => ['type' => 'multiselect', 'label' => 'Send an email for', 'options' => 'notify_events', 'default' => [], 'hint' => 'Leave all unticked for in-app alerts only.'],
                'oversight' => ['type' => 'toggle', 'label' => 'Also alert everyone who can see all loads', 'default' => false, 'hint' => 'Cancellations always alert them. Tick this to include every booking, status change and payment.'],
                'email_subject_prefix' => ['type' => 'text', 'label' => 'Email subject prefix', 'default' => '', 'max' => 40, 'hint' => 'e.g. [Acme Dispatch]'],
                'email_footer' => ['type' => 'textarea', 'label' => 'Email sign-off', 'max' => 300, 'hint' => 'Added at the bottom of every notification email.'],
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
