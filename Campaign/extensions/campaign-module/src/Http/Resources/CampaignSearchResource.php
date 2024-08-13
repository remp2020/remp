<?php

namespace Remp\CampaignModule\Http\Resources;

use Remp\CampaignModule\Campaign;
use Remp\LaravelHelpers\Resources\JsonResource;

/**
 * Class CampaignSearchResource
 *
 * @mixin Campaign
 * @package App\Http\Resources
 */
class CampaignSearchResource extends JsonResource
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
            'type' => 'campaign',
            'name' => $this->name,
            'banners' => $this->when($this->banners->isNotEmpty(), $this->banners->pluck('name')),
            'search_result_url' => route('campaigns.edit', $this)
        ];
    }
}
