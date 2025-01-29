<?php

return [
    'username' => env('DASHBOARD_USERNAME'),
    'password' => env('DASHBOARD_PASSWORD'),
    // TODO temporarily support 2 passwords, remove later after authentication is done
    'username2' => env('DASHBOARD_USERNAME2'),
    'password2' => env('DASHBOARD_PASSWORD2'),
];
