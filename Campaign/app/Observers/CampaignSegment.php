<?php

namespace App\Observers;

class CampaignSegment
{
    public function saved(\app\CampaignSegment $campaignSegment)
    {
        $campaignSegment->campaign->cache();
    }

    public function deleted(\app\CampaignSegment $campaignSegment)
    {
        $campaignSegment->campaign->cache();
    }
}
