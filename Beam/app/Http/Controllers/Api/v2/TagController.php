<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Requests\Api\v2\TopTagsSearchRequest;
use App\Model\Pageviews\Api\v2\TopSearch;

class TagController
{
    public function topTags(TopTagsSearchRequest $request, TopSearch $topSearch)
    {
        return response()->json($topSearch->topPostTags($request));
    }
}
