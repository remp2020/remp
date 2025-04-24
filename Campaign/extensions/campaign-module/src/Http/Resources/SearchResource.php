<?php

namespace Remp\CampaignModule\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Remp\LaravelHelpers\Resources\JsonResource;

class SearchResource extends JsonResource
{
    public function toArray(Request $request)
    {
        $banners = $this->resource['banners'];
        $campaigns = $this->resource['campaigns'];
        $snippets = $this->resource['snippets'];

        return Arr::collapse([
            $this->when($banners->isNotEmpty(), BannerSearchResource::collection($banners)->toArray(app('request'))),
            $this->when($campaigns->isNotEmpty(), CampaignSearchResource::collection($campaigns)->toArray(app('request'))),
            $this->when($snippets->isNotEmpty(), SnippetSearchResource::collection($snippets)->toArray(app('request'))),
        ]);
    }
}
