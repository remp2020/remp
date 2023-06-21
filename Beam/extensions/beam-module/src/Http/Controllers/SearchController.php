<?php

namespace Remp\BeamModule\Http\Controllers;

use Remp\BeamModule\Http\Requests\SearchRequest;
use Remp\BeamModule\Http\Resources\SearchResource;
use Remp\BeamModule\Model\SearchAspects\ArticleSearchAspect;
use Remp\BeamModule\Model\SearchAspects\AuthorSearchAspect;
use Remp\BeamModule\Model\SearchAspects\SegmentSearchAspect;
use Spatie\Searchable\Search;

class SearchController extends Controller
{
    public function search(SearchRequest $request)
    {
        $searchTerm = $request->query('term');

        $searchResults['articles'] = (new Search())->registerAspect(ArticleSearchAspect::class)->search($searchTerm)->pluck('searchable');

        $searchResults['authors'] = (new Search())->registerAspect(AuthorSearchAspect::class)->search($searchTerm)->pluck('searchable');
        $searchResults['authors'] = $searchResults['authors']->sortByDesc('latestPublishedArticle.0.published_at')->take(config('search.maxResultCount'));

        $searchResults['segments'] = (new Search())->registerAspect(SegmentSearchAspect::class)->search($searchTerm)->pluck('searchable');

        $searchCollection = collect($searchResults);

        return new SearchResource($searchCollection);
    }
}
