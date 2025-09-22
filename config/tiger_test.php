<?php

return [
    'api_url' => env('TIGER_SMS_API_URL'),
    'token' => env('TIGER_SMS_TOKEN'),
    'default_country' => env('TIGER_SMS_DEFAULT_COUNTRY', 'se'),
    'default_service' => env('TIGER_SMS_DEFAULT_SERVICE', 'ds'),

    // Allowed code dictionaries for validation
    'allowed_countries' => [
        // Add ISO 3166-1 alpha-2 country codes you support
        'se', 'us', 'gb', 'de', 'fr', 'es', 'it', 'nl', 'pl', 'ru',
    ],

    'allowed_services' => [
        // Add 2-letter service codes you support
        'ds', 'tg', 'wa', 'tw', 'ub', 'go', 'ig', 'fb', 'tt', 'ms',
    ],
];
