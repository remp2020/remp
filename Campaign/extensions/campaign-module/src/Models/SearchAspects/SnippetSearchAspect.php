<?php

namespace Remp\CampaignModule\Models\SearchAspects;

use Remp\CampaignModule\Snippet;
use Illuminate\Support\Collection;
use Spatie\Searchable\SearchAspect;

class SnippetSearchAspect extends SearchAspect
{
    public function getResults(string $term): Collection
    {
        return Snippet::query()
            ->where('name', 'LIKE', "%{$term}%")
            ->orderBy('name')
            ->take(config('search.maxResultCount'))
            ->get();
    }
}
