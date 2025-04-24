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

    'maxmind' => [
        'database' => base_path(env('MAXMIND_DATABASE')),
    ],

    'crm_segment' => [
        'base_url' => env('CRM_SEGMENT_API_ADDR'),
        'token' => env('CRM_SEGMENT_API_TOKEN'),
    ],

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

];
