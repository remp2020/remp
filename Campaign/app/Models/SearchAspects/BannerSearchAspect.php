<?php

namespace App\Models\SearchAspects;

use App\Banner;
use Illuminate\Support\Collection;
use Spatie\Searchable\SearchAspect;

class BannerSearchAspect extends SearchAspect
{
    public function getResults(string $term): Collection
    {
        return Banner::query()
            ->where('name', 'LIKE', "%{$term}%")
            ->orWhere('public_id', $term)
            ->orderBy('updated_at', 'DESC')
            ->take(config('search.maxResultCount'))
            ->get();
    }
}
