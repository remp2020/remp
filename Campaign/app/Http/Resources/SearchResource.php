<?php

namespace App\Http\Resources;

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

        return Arr::collapse([
            $this->when($banners->isNotEmpty(), BannerSearchResource::collection($banners)->toArray(app('response'))),
            $this->when($campaigns->isNotEmpty(), CampaignSearchResource::collection($campaigns)->toArray(app('response')))
        ]);
    }
}
