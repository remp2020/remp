<?php

namespace Remp\BeamModule\Http\Controllers\Api\v2;

use Remp\BeamModule\Http\Requests\Api\v2\TopAuthorsSearchRequest;
use Remp\BeamModule\Model\Pageviews\Api\v2\TopSearch;

class AuthorController
{
    public function topAuthors(TopAuthorsSearchRequest $request, TopSearch $topSearch)
    {
        return response()->json($topSearch->topAuthors($request));
    }
}
