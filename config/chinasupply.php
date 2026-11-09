<?php

return [
    /*
    | Locales offered on the public site. Adding a locale here (plus lang files
    | and translated content) is all that is needed to add a language; the
    | translation tables accept any locale code. zh_CN is planned for the
    | supplier portal only.
    */
    'locales' => [
        'nl' => 'Nederlands',
        'en' => 'English',
    ],

    'default_locale' => env('APP_LOCALE', 'nl'),

    'company' => [
        'name' => env('COMPANY_NAME', 'ChinaSupply'),
        'legal_name' => env('COMPANY_LEGAL_NAME'),
        'kvk' => env('COMPANY_KVK'),
        'vat' => env('COMPANY_VAT'),
        'address' => env('COMPANY_ADDRESS'),
        'email' => env('COMPANY_EMAIL', 'info@chinasupply.nl'),
    ],

    'mail' => [
        'staff_notifications' => env('STAFF_NOTIFICATION_EMAIL', 'info@chinasupply.nl'),
    ],
];
