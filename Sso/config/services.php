<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', 'your-github-app-id'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', 'your-github-app-secret'),
        'redirect' => env('GOOGLE_CALLBACK_URL', 'http://your-callback-url'),
    ],

    'remp' => [
        'beam' => [
            'web_addr' => env('REMP_BEAM_ADDR'),
        ],
        'mailer' => [
            'web_addr' => env('REMP_MAILER_ADDR'),
        ],
        'sso' => [
            'web_addr' => env('REMP_SSO_ADDR'),
        ],
        'linked' => [
            'beam' => [
                'url' => env('REMP_BEAM_ADDR'),
                'icon' => 'album',
            ],
            'campaign' => [
                'url' => env('REMP_CAMPAIGN_ADDR'),
                'icon' => 'trending-up',
            ],
            'mailer' => [
                'url' => env('REMP_MAILER_ADDR'),
                'icon' => 'email',
            ],
        ],
    ],
];
