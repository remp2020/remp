<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Resources\SearchResource;
use App\Model\SearchAspects\ArticleSearchAspect;
use App\Model\SearchAspects\AuthorSearchAspect;
use App\Model\SearchAspects\SegmentSearchAspect;
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
