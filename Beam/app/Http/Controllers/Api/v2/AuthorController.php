<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Requests\Api\v2\TopAuthorsSearchRequest;
use App\Model\Pageviews\Api\v2\TopSearch;

class AuthorController
{
    public function topAuthors(TopAuthorsSearchRequest $request, TopSearch $topSearch)
    {
        return response()->json($topSearch->topAuthors($request));
    }
}
