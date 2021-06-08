<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Requests\Api\v2\TopArticlesSearchRequest;
use App\Model\Pageviews\Api\v2\TopSearch;

class ArticleController
{
    public function topArticles(TopArticlesSearchRequest $request, TopSearch $topSearch)
    {
        return response()->json($topSearch->topArticles($request));
    }
}
