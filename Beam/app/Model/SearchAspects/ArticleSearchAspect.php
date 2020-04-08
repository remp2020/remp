<?php

namespace App\Model\SearchAspects;

use App\Article;
use Illuminate\Support\Collection;
use Spatie\Searchable\SearchAspect;

class ArticleSearchAspect extends SearchAspect
{
    public function getResults(string $term): Collection
    {
        return Article::query()
            ->where('title', 'LIKE', "%{$term}%")
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
