<?php

namespace App\Observers;

class Schedule
{
    public function saved(\app\Schedule $schedule)
    {
        $schedule->campaign->cache();
    }
}
