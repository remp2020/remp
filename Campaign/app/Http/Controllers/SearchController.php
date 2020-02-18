<?php

namespace App\Http\Controllers;

use App\Banner;
use App\Campaign;
use App\Http\Requests\SearchRequest;
use App\Http\Resources\SearchResource;

class SearchController extends Controller
{
    public function search(SearchRequest $request)
    {
        $searchTerm = $request->query('term');
        $maxResultCount = 5;

        $searchResult['banners'] = Banner::search($searchTerm)->orderBy('updated_at', 'DESC')->take($maxResultCount)->get();
        $searchResult['campaigns'] = Campaign::search($searchTerm)->with('banners')->orderBy('updated_at', 'DESC')->take($maxResultCount)->get();

        $searchCollection = collect($searchResult);

        return new SearchResource($searchCollection);
    }
}
