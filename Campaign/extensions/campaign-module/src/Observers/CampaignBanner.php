<?php

namespace Remp\CampaignModule\Observers;

class CampaignBanner
{
    public function saved(\Remp\CampaignModule\CampaignBanner $campaignBanner)
    {
        $campaignBanner->campaign->cache();
    }

    public function deleted(\Remp\CampaignModule\CampaignBanner $campaignBanner)
    {
        $campaignBanner->campaign->cache();
    }
}
