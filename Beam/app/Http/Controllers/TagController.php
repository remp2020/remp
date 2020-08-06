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
        $sections = $request->json('sections');
        $timeFrom = Carbon::parse($request->json('from'));

        return response()->json($topSearch->topPostTags($timeFrom, $limit, $sections));
    }
}
