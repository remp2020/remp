<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API ENDPOINT
    |--------------------------------------------------------------------------
    |
    | Endpoint for newsletter subscriptions
    */

    'endpoint' => env('NEWSLETTER_BANNER_API_ENDPOINT', ''),

    /*
    |--------------------------------------------------------------------------
    | USE XHR
    |--------------------------------------------------------------------------
    |
    | API ENDPOINT can be requested by XHR (1) or regular form submission (2)
    */

    'use_xhr' => !!env('NEWSLETTER_BANNER_USE_XHR', true),

    /*
    |--------------------------------------------------------------------------
    | REQUEST BODY
    |--------------------------------------------------------------------------
    |
    | Available options: x-www-form-urlencoded (default), form-data, json
    */

    'request_body' => env('NEWSLETTER_BANNER_REQUEST_BODY', 'json'),

    /*
    |--------------------------------------------------------------------------
    | REQUEST HEADERS
    |--------------------------------------------------------------------------
    |
    | Add any HTTP header you need (JSON)
    | Not applicable if used with form-data `request_body`, use `params_extra` instead.
    */

    'request_headers' => json_decode(env('NEWSLETTER_BANNER_REQUEST_HEADERS', /** @lang JSON */ '
        {
        }
    '), null, 512, JSON_THROW_ON_ERROR),

    /*
    |--------------------------------------------------------------------------
    | PARAMS TRANSPOSITION
    |--------------------------------------------------------------------------
    |
    | Specify params transposition according to your endpoint implementation
    */

    'params_transposition' => json_decode(env('NEWSLETTER_BANNER_PARAMS_TRANSPOSITION', /** @lang JSON */ '
        {
            "email": "email",
            "newsletter_id": "newsletter_id",
            "source": "source"
        }
    '), null, 512, JSON_THROW_ON_ERROR),

    /*
    |--------------------------------------------------------------------------
    | EXTRA PARAMS
    |--------------------------------------------------------------------------
    |
    | These params will be added to every request.
    | Do not use any names from NEWSLETTER_BANNER_PARAMS_TRANSPOSITION to avoid conflicts
    |
    */

    'params_extra' => env('NEWSLETTER_BANNER_PARAMS_EXTRA') ? explode(",", env('NEWSLETTER_BANNER_PARAMS_EXTRA')) : [],

];
