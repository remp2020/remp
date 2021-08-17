<?php

return [
    'enabled' => env('AIRBRAKE_ENABLED', false),

    'projectKey'    => env('AIRBRAKE_API_KEY', ''),
    'host'          => env('AIRBRAKE_API_HOST', 'api.airbrake.io'),
    'environment'   => env('APP_ENV', 'production'),

    'projectId'     => '_',
    'appVersion'    => '',
    'rootDirectory' => '',
    'httpClient'    => '',
];
