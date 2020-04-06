<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Resources\SearchResource;
use App\Models\SearchAspects\BannerSearchAspect;
use App\Models\SearchAspects\CampaignSearchAspect;
use Spatie\Searchable\Search;

class SearchController extends Controller
{
    public function search(SearchRequest $request)
    {
        $searchTerm = $request->query('term');

        $searchResults['banners'] = (new Search())->registerAspect(BannerSearchAspect::class)->search($searchTerm)->pluck('searchable');
        $searchResults['campaigns'] = (new Search())->registerAspect(CampaignSearchAspect::class)->search($searchTerm)->pluck('searchable');

        $searchCollection = collect($searchResults);

        return new SearchResource($searchCollection);
    }
}
