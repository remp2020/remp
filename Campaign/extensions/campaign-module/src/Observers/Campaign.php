<?php

namespace Remp\CampaignModule\Observers;

class Campaign
{
    public function saved(\Remp\CampaignModule\Campaign $campaign)
    {
        $campaign->cache();
    }

    public function pivotAttached(\Remp\CampaignModule\Campaign $campaign)
    {
        $campaign->cache();
    }

    public function pivotDetached(\Remp\CampaignModule\Campaign $campaign)
    {
        $campaign->cache();
    }

    public function pivotUpdated(\Remp\CampaignModule\Campaign $campaign)
    {
        $campaign->cache();
    }
}
