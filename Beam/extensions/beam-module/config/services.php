<?php

return [
    'beam' => [
        'web_addr' => env('REMP_BEAM_ADDR'),
        'tracker_addr' => env('REMP_TRACKER_ADDR'),
        'tracker_property_token' => env('REMP_TRACKER_ADDR'),
        'segments_addr' => env('REMP_SEGMENTS_ADDR'),
        'segments_auth_token' => env('REMP_SEGMENTS_AUTH_TOKEN'),
    ],
    'campaign' => [
        'web_addr' => env('REMP_CAMPAIGN_ADDR'),
    ],
    'mailer' => [
        'web_addr' => env('REMP_MAILER_ADDR'),
        'api_token' => env('REMP_MAILER_API_TOKEN')
    ],
    'sso' => [
        'web_addr' => env('REMP_SSO_ADDR'),
        'api_token' => env('REMP_SSO_API_TOKEN'),
    ],
    'linked' => [
        'beam' => [
            'url' => '/',
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
];
