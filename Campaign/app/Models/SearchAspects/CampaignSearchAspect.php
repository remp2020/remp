<?php

namespace App\Models\SearchAspects;

use App\Campaign;
use Illuminate\Support\Collection;
use Spatie\Searchable\SearchAspect;

class CampaignSearchAspect extends SearchAspect
{
    public function getResults(string $term): Collection
    {
        return Campaign::query()
            ->where('name', 'LIKE', "%{$term}%")
            ->orWhereHas('banners', function ($query) use ($term) {
                $query->where('name', 'LIKE', "%{$term}%");
            })
            ->orderBy('updated_at', 'DESC')
            ->take(config('search.maxResultCount'))
            ->with('banners')
            ->get();
    }
}
