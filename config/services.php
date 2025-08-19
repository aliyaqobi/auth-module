<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Google OAuth Configuration
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),

        // Additional security settings
        'scopes' => [
            'openid',
            'profile',
            'email'
        ],

        // OAuth security settings
        'state_verification' => env('GOOGLE_STATE_VERIFICATION', true),
        'token_expiry_days' => env('GOOGLE_TOKEN_EXPIRY_DAYS', 30),

        // Rate limiting settings
        'rate_limit' => [
            'per_minute' => env('GOOGLE_OAUTH_RATE_LIMIT_MINUTE', 10),
            'per_hour' => env('GOOGLE_OAUTH_RATE_LIMIT_HOUR', 50),
        ],

        // Allowed redirect domains for security
        'allowed_redirect_domains' => array_filter([
            env('FRONTEND_APP_URL'),
            env('APP_URL'),
            'https://accounts.google.com',
        ]),
    ],
];
