<?php

namespace Remp\BeamModule\Model\SearchAspects;

use Remp\BeamModule\Model\Article;
use Illuminate\Support\Collection;
use Spatie\Searchable\SearchAspect;

class ArticleSearchAspect extends SearchAspect
{
    public function getResults(string $term): Collection
    {
        return Article::query()
            ->where('title', 'LIKE', "%{$term}%")
            ->orWhere('external_id', '=', $term)
            ->orWhereHas('tags', function ($query) use ($term) {
                $query->where('name', 'LIKE', "{$term}%");
            })
            ->orWhereHas('sections', function ($query) use ($term) {
                $query->where('name', 'LIKE', "{$term}%");
            })
            ->orderBy('published_at', 'DESC')
            ->take(config('search.maxResultCount'))
            ->with(['tags', 'sections'])
            ->get();
    }
}
