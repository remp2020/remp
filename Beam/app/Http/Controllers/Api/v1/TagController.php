<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\TopTagsSearchRequest;
use App\Model\Pageviews\Api\v1\TopSearch;

class TagController
{
    public function topTags(TopTagsSearchRequest $request, TopSearch $topSearch)
    {
        return response()->json($topSearch->topPostTags($request));
    }
}
