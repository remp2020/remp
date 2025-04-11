<?php

namespace Remp\CampaignModule\Http\Resources;

use Illuminate\Support\Arr;
use Remp\LaravelHelpers\Resources\JsonResource;

class SearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $banners = $this->get('banners');
        $campaigns = $this->get('campaigns');
        $snippets = $this->get('snippets');

        return Arr::collapse([
            $this->when($banners->isNotEmpty(), BannerSearchResource::collection($banners)->toArray(app('request'))),
            $this->when($campaigns->isNotEmpty(), CampaignSearchResource::collection($campaigns)->toArray(app('request'))),
            $this->when($snippets->isNotEmpty(), SnippetSearchResource::collection($snippets)->toArray(app('request'))),
        ]);
    }
}
