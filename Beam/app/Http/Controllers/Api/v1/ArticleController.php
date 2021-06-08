<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\TopArticlesSearchRequest;
use App\Model\Pageviews\Api\v1\TopSearch;

class ArticleController
{
    public function topArticles(TopArticlesSearchRequest $request, TopSearch $topSearch)
    {
        return response()->json($topSearch->topArticles($request));
    }
}
