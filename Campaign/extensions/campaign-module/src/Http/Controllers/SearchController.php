<?php

namespace Remp\CampaignModule\Http\Controllers;

use Remp\CampaignModule\Http\Requests\SearchRequest;
use Remp\CampaignModule\Http\Resources\SearchResource;
use Remp\CampaignModule\Models\SearchAspects\BannerSearchAspect;
use Remp\CampaignModule\Models\SearchAspects\CampaignSearchAspect;
use Remp\CampaignModule\Models\SearchAspects\SnippetSearchAspect;
use Spatie\Searchable\Search;

class SearchController extends Controller
{
    public function search(SearchRequest $request)
    {
        $searchTerm = $request->query('term');

        $searchResults['banners'] = (new Search())->registerAspect(BannerSearchAspect::class)->search($searchTerm)->pluck('searchable');
        $searchResults['campaigns'] = (new Search())->registerAspect(CampaignSearchAspect::class)->search($searchTerm)->pluck('searchable');
        $searchResults['snippets'] = (new Search())->registerAspect(SnippetSearchAspect::class)->search($searchTerm)->pluck('searchable');
        $searchCollection = collect($searchResults);

        return new SearchResource($searchCollection);
    }
}
