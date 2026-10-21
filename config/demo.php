<?php

return [
    // Master switch for the public "Try the live demo" button.
    'enabled' => (bool) env('DEMO_ENABLED', true),

    // How long one visitor's sandbox lives.
    'minutes' => (int) env('DEMO_MINUTES', 60),

    // Abuse limits. One IP address may start this many demos in 24 hours...
    'per_ip_per_day' => (int) env('DEMO_PER_IP_PER_DAY', 2),

    // ...and the whole site only keeps this many sandboxes alive / starts this many an hour,
    // so a botnet can fill neither the database nor the CPU.
    'max_active' => (int) env('DEMO_MAX_ACTIVE', 30),
    'max_per_hour' => (int) env('DEMO_MAX_PER_HOUR', 40),

    // A human takes a moment to click; a script posting the form straight away is refused.
    'min_fill_seconds' => (int) env('DEMO_MIN_FILL_SECONDS', 3),

    // Optional Cloudflare Turnstile (free CAPTCHA). Leave empty to rely on the limits above.
    'turnstile' => [
        'site_key' => env('DEMO_TURNSTILE_SITE_KEY'),
        'secret' => env('DEMO_TURNSTILE_SECRET'),
    ],

    // Inside a demo: files this small, and at most this many documents per workspace.
    'max_upload_kb' => 1024,
    'max_attachments' => 60,

    // The sandbox plan's caps (seeded data counts towards them).
    'plan' => ['max_users' => 12, 'max_drivers' => 25, 'max_loads_per_month' => 60],

    // Mutating requests to these routes are refused inside a demo (GET stays open unless listed in `hidden`).
    'blocked' => [
        'profile.*', 'password.*', 'settings.*', 'custom-fields.*', 'tax-rules.*', 'commission-tiers.*',
        'users.*', 'roles.*', 'saved-reports.*',
    ],
    'hidden' => ['data.*', 'billing.*'],
];
