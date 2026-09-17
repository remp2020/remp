<?php

namespace Remp\CampaignModule\Observers;

use Remp\CampaignModule\Contracts\SegmentAggregator;

class Campaign
{
    public function saved(\Remp\CampaignModule\Campaign $campaign): void
    {
        $campaign->cache();
        $this->serializeMaps();
    }

    public function pivotAttached(\Remp\CampaignModule\Campaign $campaign): void
    {
        $campaign->cache();
        $this->serializeMaps();
    }

    public function pivotDetached(\Remp\CampaignModule\Campaign $campaign): void
    {
        $campaign->cache();
        $this->serializeMaps();
    }

    public function pivotUpdated(\Remp\CampaignModule\Campaign $campaign): void
    {
        $campaign->cache();
        $this->serializeMaps();
    }

    private function serializeMaps(): void
    {
        app(SegmentAggregator::class)->serializeToRedis();
    }
}
