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

    'maxmind' => [
        'database' => base_path(env('MAXMIND_DATABASE')),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'crm_segment' => [
        'base_url' => env('CRM_SEGMENT_API_ADDR'),
        'token' => env('CRM_SEGMENT_API_TOKEN'),
    ],

    'remp' => [
        'beam' => [
            'web_addr' => env('REMP_BEAM_ADDR'),
            'segments_addr' => env('REMP_SEGMENTS_ADDR'),
            'segments_timeout' => (int) env('REMP_SEGMENTS_TIMEOUT') ?: 5,
        ],
        'mailer' => [
            'web_addr' => env('REMP_MAILER_ADDR'),
        ],
        'sso' => [
            'web_addr' => env('REMP_SSO_ADDR'),
            'api_token' => env('REMP_SSO_API_TOKEN'),
        ],
        'pythia' => [
            'segments_addr' => env('REMP_PYTHIA_SEGMENTS_ADDR'),
            'segments_timeout' => (int) env('REMP_PYTHIA_SEGMENTS_TIMEOUT') ?: 1,
        ],
        'linked' => [
            'beam' => [
                'url' => env('REMP_BEAM_ADDR'),
                'icon' => 'album',
            ],
            'campaign' => [
                'url' => '/',
                'icon' => 'trending-up',
            ],
            'mailer' => [
                'url' => env('REMP_MAILER_ADDR'),
                'icon' => 'email',
            ],
        ],
    ],

];
