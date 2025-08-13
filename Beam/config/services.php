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

    'azure_computer_vision' => [
        'endpoint' => env('AZURE_COMPUTER_VISION_ENDPOINT'),
        'api_key' => env('AZURE_COMPUTER_VISION_API_KEY'),
        'api_version' => env('AZURE_COMPUTER_VISION_API_VERSION', '2024-02-01'),
    ],

    'gorse_recommendation' => [
        'endpoint' => env('GORSE_RECOMMENDATION_ENDPOINT'),
        'api_key' => env('GORSE_RECOMMENDATION_API_KEY'),
        'url_filter' => env('GORSE_RECOMMENDATION_URL_FILER'),
    ],
];
