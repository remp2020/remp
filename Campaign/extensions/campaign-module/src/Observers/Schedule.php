<?php

namespace Remp\CampaignModule\Observers;

class Schedule
{
    public function saved(\Remp\CampaignModule\Schedule $schedule)
    {
        $schedule->campaign->cache();
    }
}
