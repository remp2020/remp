<?php

namespace App\Http\Showtime;

use App\Campaign;
use App\CampaignBanner;

class ShowtimeTestable extends Showtime
{
    public function shouldDisplay(Campaign $campaign, $userData, array &$activeCampaigns,): ?CampaignBanner
    {
        $result = parent::campaignBannerToDisplay($campaign, $userData, $activeCampaigns);
        // return banner or null
        return is_string($result) ? null : $result;
    }
}
