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

        $sections = $request->json('sections');
        $sectionValueType = null;
        $sectionValues = null;
        if (isset($sections['name'])) {
            $sectionValueType = 'name';
            $sectionValues = $sections['name'];
        } elseif (isset($sections['external_id'])) {
            $sectionValueType = 'external_id';
            $sectionValues = $sections['external_id'];
        }

        return response()->json($topSearch->topPostTags($timeFrom, $limit, $sectionValueType, $sectionValues));
    }
}
