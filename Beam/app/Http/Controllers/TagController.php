<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopTagsRequest;
use App\Model\Pageviews\TopSearch;
use Illuminate\Support\Carbon;

class TagController extends Controller
{
    public function topTags(TopTagsRequest $request, TopSearch $topSearch)
    {
        $limit = $request->json('limit');
        $timeFrom = Carbon::parse($request->json('from'));

        return response()->json($topSearch->topPostTags($timeFrom, $limit));
    }
}
