<?php

namespace App;

use League\Uri\Components\Query;
use League\Uri\Schemes\Http;

class UrlHelper
{
    /**
     * appendQueryParams parses provided URL and appends key-value $params as next query parameters to the URL.
     * Updated URL is returned.
     *
     * @param $originalUrl string valid URL
     * @param array $params key value pairs
     * @return string
     */
    public function appendQueryParams($originalUrl, array $params) {
        $url = Http::createFromString($originalUrl);
        $queryPairs = Query::parse($url->getQuery());
        $query = Query::createFromPairs($queryPairs)
            ->append(
                Query::build($params)
            )
            ->getContent();
        return $url->withQuery($query)->__toString();
    }
}