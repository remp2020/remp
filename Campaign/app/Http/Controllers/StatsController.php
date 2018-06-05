<?php

namespace App\Http\Controllers;

use App\Campaign;
use App\Contracts\Remp\Stats;

class StatsController extends Controller
{
    public function campaignClicks(Campaign $campaign, Stats $stats)
    {
        $result = $stats->count()
                        ->events('banner', 'click')
                        ->forCampaign($campaign->uuid)
                        ->get();

        return response()->json($result);
    }

    public function campaignStartedPayments(Campaign $campaign, Stats $stats)
    {
        $result = $stats->count()
                        ->commerce('payment')
                        ->forCampaign($campaign->uuid)
                        ->get();

        return response()->json($result);
    }

    public function campaignFinishedPayments(Campaign $campaign, Stats $stats)
    {
        $result = $stats->count()
                        ->commerce('purchase')
                        ->forCampaign($campaign->uuid)
                        ->get();

        return response()->json($result);
    }

    public function campaignEarned(Campaign $campaign, Stats $stats)
    {
        $result = $stats->sum()
            ->commerce('purchase')
            ->forCampaign($campaign->uuid)
            ->get();

        return response()->json($result);
    }

    public function campaignShowHistogram(Campaign $campaign, $interval, Stats $stats)
    {
        $result = $stats->count()
                        ->events('banner', 'show')
                        ->forCampaign($campaign->uuid)
                        ->timeHistogram($interval)
                        ->get();

        return response()->json($result);
    }
}
