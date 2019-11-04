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
    'aggregated_data_retention_period' => env('AGGREGATED_DATA_RETENTION_PERIOD', 90),

    /*
    |--------------------------------------------------------------------------
    | Pageviews data source
    |--------------------------------------------------------------------------
    |
    | Two values are allowed:
    | snapshots - loaded from DB snapshots of Journal API
    | journal - loaded directly from Journal API
    |
    */
    'pageviews_data_source' => env('PAGEVIEWS_DATA_SOURCE', 'snapshots')
];
