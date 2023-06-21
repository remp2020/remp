<?php

namespace Remp\BeamModule\Http\Controllers\Api\v2;

use Remp\BeamModule\Http\Requests\Api\v2\TopTagsSearchRequest;
use Remp\BeamModule\Model\Pageviews\Api\v2\TopSearch;

class TagController
{
    public function topTags(TopTagsSearchRequest $request, TopSearch $topSearch)
    {
        return response()->json($topSearch->topPostTags($request));
    }
}
