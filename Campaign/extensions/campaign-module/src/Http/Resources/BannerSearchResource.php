<?php

namespace Remp\CampaignModule\Http\Resources;

use Illuminate\Http\Request;
use Remp\CampaignModule\Banner;
use Remp\LaravelHelpers\Resources\JsonResource;

/**
 * @mixin Banner
 */
class BannerSearchResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'type' => 'banner',
            'name' => $this->name,
            'search_result_url' => route('banners.edit', $this)
        ];
    }
}
