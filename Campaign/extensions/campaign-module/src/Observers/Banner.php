<?php

namespace Remp\CampaignModule\Observers;

class Banner
{
    public function saved(\Remp\CampaignModule\Banner $banner)
    {
        $banner->cache();

        /** @var \Remp\CampaignModule\Campaign $campaign */
        foreach ($banner->campaigns as $campaign) {
            $campaign->cache();
        }
    }
}
