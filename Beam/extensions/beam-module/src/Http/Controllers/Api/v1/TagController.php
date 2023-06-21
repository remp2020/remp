<?php

namespace Remp\BeamModule\Http\Controllers\Api\v1;

use Remp\BeamModule\Http\Requests\Api\v1\TopTagsSearchRequest;
use Remp\BeamModule\Model\Pageviews\Api\v1\TopSearch;

class TagController
{
    public function topTags(TopTagsSearchRequest $request, TopSearch $topSearch)
    {
        return response()->json($topSearch->topPostTags($request));
    }
}
