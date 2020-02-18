<?php

namespace App\Http\Controllers;

use App\Article;
use App\Author;
use App\Http\Requests\SearchRequest;
use App\Http\Resources\SearchResource;
use App\Segment;

class SearchController extends Controller
{
    public function search(SearchRequest $request)
    {
        $searchTerm = $request->query('term');
        $maxResultCount = 5;

        $searchResult['articles'] = Article::search($searchTerm)->with(['tags', 'sections'])->orderBy('published_at', 'DESC')->take($maxResultCount)->get();

        $searchResult['authors'] = Author::search($searchTerm)->get();
        $searchResult['authors']->each(function ($author) {
            $author->load('latestPublishedArticle');
        });
        $searchResult['authors'] = $searchResult['authors']->sortByDesc('latestPublishedArticle.0.published_at')->slice(0, $maxResultCount);

        $searchResult['segments'] = Segment::search($searchTerm)->orderBy('updated_at', 'DESC')->take($maxResultCount)->get();

        $searchCollection = collect($searchResult);

        return new SearchResource($searchCollection);
    }
}
