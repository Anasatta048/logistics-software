<?php

/*
| Plain-language names for the permission matrix on the Roles screen, so a
| first-time admin reads "Mark loads as paid" instead of "loads.paid".
| Unknown modules/actions fall back to a prettified version of their key.
*/
return [
    'modules' => [
        'loads' => ['Loads', 'truck', 'Book, move and close out loads'],
        'companies' => ['Companies', 'building', 'Carrier companies you work with'],
        'drivers' => ['Drivers', 'users', 'Drivers and their trucks'],
        'brokers' => ['Brokers', 'briefcase', 'Brokers who send you freight'],
        'equipment-types' => ['Equipment types', 'package', 'Trailer / truck types'],
        'invoices' => ['Invoices', 'file-text', 'Bill companies for delivered loads'],
        'company-payments' => ['Payment receipts', 'credit-card', 'Money received from companies'],
        'currency-conversions' => ['Currency conversions', 'repeat', 'USD to PKR conversions'],
        'expenses' => ['Expenses', 'receipt', 'Company costs and spending'],
        'accounts' => ['Chart of accounts', 'layers', 'The account list behind the books'],
        'journal-vouchers' => ['Journal vouchers', 'note', 'Manual accounting entries'],
        'commissions' => ['Commissions', 'percent', 'Dispatcher commission payouts'],
        'reports' => ['Reports', 'chart-bar', 'Business reports and exports'],
        'users' => ['Users', 'user', 'People who can sign in'],
        'roles' => ['Roles & rights', 'key', 'Who can do what'],
    ],

    'actions' => [
        'view' => ['See', 'Can open and read it'],
        'create' => ['Add new', 'Can create new ones'],
        'edit' => ['Change', 'Can edit existing ones'],
        'delete' => ['Remove', 'Can remove them'],
        'cancel' => ['Cancel', 'Can cancel a load'],
        'load' => ['Mark picked up / loaded / delivered', 'Can move a load through its trip stages'],
        'paid' => ['Mark as paid', 'Can close a load as paid'],
        'approve' => ['Approve', 'Can approve entries'],
        'void' => ['Void', 'Can void entries'],
        'pay' => ['Pay out', 'Can record a payout'],
    ],
];
