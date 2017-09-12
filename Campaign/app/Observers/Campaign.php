<?php

namespace App\Observers;

class Campaign
{
    public function saved(\app\Campaign $campaign)
    {
        $campaign->cache();
    }
}
