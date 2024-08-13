<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Maximum number of returned search results
    |--------------------------------------------------------------------------
    |
    | This value represents limit for number of returned search results.
    | IMPORTANT: this number affects each searchable entity separately
    | e.g.: when maxResultCount is being set to 5 and you search
    | model_1 and model_2 you can get max 10 results
    */

    'maxResultCount' => env('SEARCH_MAX_RESULT_COUNT', 5),
];
