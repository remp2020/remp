<?php

return [

  /**
   * Should we send errors to Airbrake
   */
  'enabled'             => env('AIRBRAKE_ENABLED', 'false'),

  /**
   * Airbrake API key
   */
  'api_key'             => env('AIRBRAKE_API_KEY', ''),

  /**
   * Should we send errors async
   */
  'async'               => false,

  /**
   * Which enviroments should be ingored? (ex. local)
   */
  'ignore_environments' => [],

  /**
   * Ignore these exceptions
   */
  'ignore_exceptions'   => [],

  /**
   * Connection to the airbrake server
   */
  'connection'          => [

    'host'      => env('AIRBRAKE_API_HOST', 'api.airbrake.io'),

    'port'      => '443',

    'secure'    => true,

    'verifySSL' => true
  ]

];
