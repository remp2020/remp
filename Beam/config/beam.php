<?php

// Basic Beam settings
return [
    /*
    |--------------------------------------------------------------------------
    | Data retention/compression period
    |--------------------------------------------------------------------------
    |
    | Beam aggregates data from Segments API, here we set up how long these data are kept for.
    | Negative value means data are kept indefinitely.
    |
    */
    'aggregated_data_retention_period' => env('AGGREGATED_DATA_RETENTION_PERIOD', 90)
];
