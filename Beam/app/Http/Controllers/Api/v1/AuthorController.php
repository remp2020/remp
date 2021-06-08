<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\v1\TopAuthorsSearchRequest;
use App\Model\Pageviews\Api\v1\TopSearch;

class AuthorController
{
    public function topAuthors(TopAuthorsSearchRequest $request, TopSearch $topSearch)
    {
        return response()->json($topSearch->topAuthors($request));
    }
}
