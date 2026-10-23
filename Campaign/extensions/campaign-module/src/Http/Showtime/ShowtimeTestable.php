<?php

namespace Remp\CampaignModule\Http\Showtime;

use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\Models\Campaign\CampaignBannerView;
use Remp\CampaignModule\Models\Campaign\CampaignView;

class ShowtimeTestable extends Showtime
{
    public function shouldDisplay(Campaign $campaign, $userData, array &$activeCampaigns): ?CampaignBannerView
    {
        $result = parent::campaignBannerToDisplay(self::cachedCampaign($campaign), $userData, $activeCampaigns);
        // return banner or null
        return is_string($result) ? null : $result;
    }

    // showtime evaluates the campaign decoded from its cached JSON
    public static function cachedCampaign(Campaign $campaign): CampaignView
    {
        return CampaignView::fromCache(json_decode(json_encode(CampaignView::fromModel($campaign)->toCache()), true));
    }
}
