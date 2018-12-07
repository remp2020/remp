<?php

return [
    'enabled' => env('AIRBRAKE_ENABLED', env('APP_ENV') !== 'local'),

    'projectKey'    => env('AIRBRAKE_API_KEY', ''),
    'host'          => env('AIRBRAKE_API_HOST', 'api.airbrake.io'),
    'environment'   => env('APP_ENV', 'production'),

    'projectId'     => '_',
    'appVersion'    => '',
    'rootDirectory' => '',
    'httpClient'    => '',
];