<?php

namespace Remp\CampaignModule\Http\Resources;

use Remp\CampaignModule\Banner;
use Remp\LaravelHelpers\Resources\JsonResource;

/**
 * Class BannerSearchResource
 *
 * @mixin Banner
 * @package App\Http\Resources
 */
class BannerSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'type' => 'banner',
            'name' => $this->name,
            'search_result_url' => route('banners.edit', $this)
        ];
    }
}
