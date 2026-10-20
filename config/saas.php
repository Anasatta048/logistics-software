<?php

return [
    // Where "new access request" alerts go. Falls back to the first super admin.
    'notify_email' => env('SAAS_NOTIFY_EMAIL'),

    // Shown to customers whose workspace is locked or who need help.
    'support_email' => env('SAAS_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS')),

    // Equipment types every new workspace starts with.
    'starter_equipment_types' => ['Dry Van', 'Reefer', 'Flatbed', 'Power Only', 'Box Truck'],
];
