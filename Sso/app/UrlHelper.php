<?php

namespace App;

use League\Uri\Components\Query;
use League\Uri\Http;
use League\Uri\QueryString;
use League\Uri\Uri;

class UrlHelper
{
    /**
     * appendQueryParams parses provided URL and appends key-value $params as next query parameters to the URL.
     * Updated URL is returned.
     */
    public function appendQueryParams(string $originalUrl, array $params): string
    {
        $url = Uri::new($originalUrl);

        $query = Query::new($url->getQuery());
        foreach ($params as $key => $value) {
            $query = $query->appendTo($key, $value);
        }

        return $url->withQuery($query->value());
    }
}
