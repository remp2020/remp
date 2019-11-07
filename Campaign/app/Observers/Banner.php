<?php

namespace App\Observers;

class Banner
{
    public function saved(\app\Banner $banner)
    {
        $banner->cache();

        /** @var \App\Campaign $campaign */
        foreach ($banner->campaigns as $campaign) {
            $campaign->cache();
        }
    }
}
