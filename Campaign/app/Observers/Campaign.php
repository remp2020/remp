<?php

namespace App\Observers;

class Campaign
{
    public function saved(\app\Campaign $campaign)
    {
        $campaign->cache();
    }

    public function pivotAttached(\app\Campaign $campaign)
    {
        $campaign->cache();
    }

    public function pivotDetached(\app\Campaign $campaign)
    {
        $campaign->cache();
    }

    public function pivotUpdated(\app\Campaign $campaign)
    {
        $campaign->cache();
    }
}
