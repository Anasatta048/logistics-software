<?php

/*
|--------------------------------------------------------------------------
| Invoice letterhead
|--------------------------------------------------------------------------
|
| Printed on every driver invoice PDF. Drop a logo at public/images/logo.png
| (PNG, roughly 300x100) and it is picked up automatically; until then the
| company name is set as text.
|
*/

return [
    'company_name' => env('INVOICE_COMPANY_NAME', 'Dreamelect Logistics LLC'),
    'website' => env('INVOICE_WEBSITE', 'www.dreamelectlogistics.com'),
    'email' => env('INVOICE_EMAIL', 'Invoice@dreamelectlogistics.com'),
    'phone' => env('INVOICE_PHONE', '(512) 270-0777'),
    'start_number' => (int) env('INVOICE_START_NUMBER', 1001),
    'due_days' => (int) env('INVOICE_DUE_DAYS', 1),
    'brand_color' => env('INVOICE_BRAND_COLOR', '#E8742F'),
];
