<?php

namespace Remp\CampaignModule\Http\Showtime;

use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBanner;

class ShowtimeTestable extends Showtime
{
    public function shouldDisplay(Campaign $campaign, $userData, array &$activeCampaigns,): ?CampaignBanner
    {
        $result = parent::campaignBannerToDisplay($campaign, $userData, $activeCampaigns);
        // return banner or null
        return is_string($result) ? null : $result;
    }
}
