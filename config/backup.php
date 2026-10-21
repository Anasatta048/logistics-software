<?php

return [
    // Where dumps are kept on this server (outside the web root).
    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    // Local copies older than this are deleted after each backup.
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 14),

    // Optional second copy: the name of a filesystem disk (e.g. "s3"). Empty = local only.
    'offsite_disk' => env('BACKUP_OFFSITE_DISK'),

    // Also zip uploaded files (rate confirmations, BOL/POD, payment slips)?
    'include_files' => (bool) env('BACKUP_INCLUDE_FILES', true),

    // Client binaries. On Linux "mysqldump" / "mysql" on the PATH is enough; on XAMPP point to its bin folder.
    'mysqldump' => env('BACKUP_MYSQLDUMP', 'mysqldump'),
    'mysql' => env('BACKUP_MYSQL', 'mysql'),

    // /health reports a failure when the newest backup is older than this many hours.
    'max_age_hours' => (int) env('BACKUP_MAX_AGE_HOURS', 36),
];
