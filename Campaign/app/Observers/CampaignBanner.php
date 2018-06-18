<?php

namespace App\Observers;

class CampaignBanner
{
    public function saved(\app\CampaignBanner $campaignBanner)
    {
        $campaignBanner->campaign->cache();
    }

    public function updated(\app\CampaignBanner $campaignBanner)
    {
        $campaignBanner->campaign->cache();
    }
}
