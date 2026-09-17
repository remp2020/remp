<?php

namespace Remp\CampaignModule\Observers;

use Remp\CampaignModule\Contracts\SegmentAggregator;

class Banner
{
    public function saved(\Remp\CampaignModule\Banner $banner)
    {
        $banner->cache();

        /** @var \Remp\CampaignModule\Campaign $campaign */
        foreach ($banner->campaigns as $campaign) {
            $campaign->cache();
        }

        // Keep showtime.php's config-based maps (color schemes, ...) in sync with the running app's config.
        app(SegmentAggregator::class)->serializeToRedis();
    }
}
