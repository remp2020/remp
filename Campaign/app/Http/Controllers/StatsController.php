<?php

namespace App\Http\Controllers;

use App\Campaign;
use App\Contracts\Remp\Stats;
use App\CampaignBanner;

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

    public function campaignClicksHistogram(Campaign $campaign, $interval, Stats $stats)
    {
        $result = $stats->count()
                ->events('banner', 'click')
                ->forCampaign($campaign->uuid)
                ->timeHistogram($interval)
                ->get();

        if ($result["success"] != true) {
            return response()->json($result);
        }

        $histogramData = $result['data'][0];
        $data = $this->parseHistogramData($histogramData);

        return response()->json($data);
    }

    public function variantClicks(CampaignBanner $variant, Stats $stats)
    {
        $result = $stats->count()
                        ->events('banner', 'click')
                        ->forVariant($variant->uuid)
                        ->get();

        return response()->json($result);
    }

    public function variantShows(CampaignBanner $variant, Stats $stats)
    {
        $result = $stats->count()
                        ->events('banner', 'show')
                        ->forVariant($variant->uuid)
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

    public function campaignShowsHistogram(Campaign $campaign, $interval, Stats $stats)
    {
        $result = $stats->count()
                        ->events('banner', 'show')
                        ->forCampaign($campaign->uuid)
                        ->timeHistogram($interval)
                        ->get();

        if ($result["success"] != true) {
            return response()->json($result);
        }

        $histogramData = $result['data'][0];
        $data = $this->parseHistogramData($histogramData);

        return response()->json($data);
    }

    public function parseHistogramData($histogramData)
    {
        $parsedData = [
            'labels' => [],
            'data' => []
        ];

        foreach ($histogramData->time_histogram as $histogramRow) {
            $date = new \DateTime($histogramRow->time);

            $parsedData['labels'][] = $date->format('d. m. Y');
            $parsedData['data'][] = $histogramRow->value;
        }

        return $parsedData;
    }
}
