<?php

namespace App\Model\SearchAspects;

use App\Segment;
use Illuminate\Support\Collection;
use Spatie\Searchable\SearchAspect;

class SegmentSearchAspect extends SearchAspect
{
    public function getResults(string $term): Collection
    {
        return Segment::query()
            ->where('name', 'LIKE', "{$term}%")
            ->orWhere('code', 'LIKE', "{$term}%")
            ->orderBy('updated_at', 'DESC')
            ->take(config('search.maxResultCount'))
            ->get();
    }
}
