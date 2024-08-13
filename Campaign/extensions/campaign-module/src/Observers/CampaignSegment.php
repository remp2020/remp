<?php

namespace Remp\CampaignModule\Observers;

class CampaignSegment
{
    public function saved(\Remp\CampaignModule\CampaignSegment $campaignSegment)
    {
        $campaignSegment->campaign->cache();
    }

    public function deleted(\Remp\CampaignModule\CampaignSegment $campaignSegment)
    {
        $campaignSegment->campaign->cache();
    }
}
