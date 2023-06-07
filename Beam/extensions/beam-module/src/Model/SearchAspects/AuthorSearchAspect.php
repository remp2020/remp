<?php

namespace Remp\BeamModule\Model\SearchAspects;

use Remp\BeamModule\Model\Author;
use Illuminate\Support\Collection;
use Spatie\Searchable\SearchAspect;

class AuthorSearchAspect extends SearchAspect
{
    public function getResults(string $term): Collection
    {
        return Author::query()
            ->where('name', 'LIKE', "%{$term}%")
            ->with('latestPublishedArticle')
            ->get();
    }
}
