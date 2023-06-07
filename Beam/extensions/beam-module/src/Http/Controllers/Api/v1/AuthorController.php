<?php

namespace Remp\BeamModule\Http\Controllers\Api\v1;

use Remp\BeamModule\Http\Requests\Api\v1\TopAuthorsSearchRequest;
use Remp\BeamModule\Model\Pageviews\Api\v1\TopSearch;

class AuthorController
{
    public function topAuthors(TopAuthorsSearchRequest $request, TopSearch $topSearch)
    {
        return response()->json($topSearch->topAuthors($request));
    }
}
