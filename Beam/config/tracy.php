<?php

return [
    'enabled' => env('APP_DEBUG') === true,
    'showBar' => env('APP_ENV') !== 'production',
    'accepts'      => [
        'text/html',
    ],
    'appendTo' => 'body',
    'editor' => env('APP_DEBUG_EDITOR', 'phpstorm://open?file=%file&line=%line'),
    'maxDepth' => 4,
    'maxLength' => 1000,
    'scream' => true,
    'showLocation' => true,
    'strictMode' => true,
    'panels' => [
        'routing' => true,
        'database' => true,
        'view' => true,
        'event' => true,
        'session' => true,
        'request' => true,
        'auth' => true,
        'html-validator' => true,
        'terminal' => true,
    ],
];
