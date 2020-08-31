<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopSearchRequest;
use App\Model\Pageviews\TopSearch;
use Illuminate\Support\Carbon;

class TagController extends Controller
{
    public function topTags(TopSearchRequest $request, TopSearch $topSearch)
    {
        $limit = $request->json('limit');
        $timeFrom = Carbon::parse($request->json('from'));

        $sections = $request->json('sections');
        $sectionValueType = null;
        $sectionValues = null;
        if (isset($sections['external_id'])) {
            $sectionValueType = 'external_id';
            $sectionValues = $sections['external_id'];
        } elseif (isset($sections['name'])) {
            $sectionValueType = 'name';
            $sectionValues = $sections['name'];
        }
        $contentType = $request->json('content_type');

        return response()->json($topSearch->topPostTags($timeFrom, $limit, $sectionValueType, $sectionValues, $contentType));
    }
}
