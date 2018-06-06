<?php

namespace App\Http\Controllers;

use App\Campaign;
use App\CampaignBanner;
use Illuminate\Http\Request;
use App\Contracts\Remp\Stats;

class StatsController extends Controller
{

    public function campaignStatsCount(Campaign $campaign, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->events('banner', $request->get('type'))
                        ->forCampaign($campaign->uuid)
                        ->get();

        return response()->json($result);
    }

    public function campaignStatsHistogram(Campaign $campaign, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->events('banner', $request->get('type'))
                        ->forCampaign($campaign->uuid)
                        ->timeHistogram($request->get('interval'))
                        ->get();

        if ($result["success"] != true) {
            return response()->json($result);
        }

        $histogramData = $result['data'][0];

        $data = $this->parseHistogramData($histogramData);

        return response()->json($data);
    }

    public function campaignPaymentStatsCount(Campaign $campaign, Stats $stats, Request $request)
    {
        $result = $stats->count()
            ->commerce($request->get('type'))
            ->forCampaign($campaign->uuid)
            ->get();

        return response()->json($result);
    }

    public function campaignPaymentStatsHistogram(Campaign $campaign, Stats $stats, Request $request)
    {
        $result = $stats->count()
            ->commerce($request->get('type'))
            ->forCampaign($campaign->uuid)
            ->get();

        if ($result["success"] != true) {
            return response()->json($result);
        }

        $histogramData = $result['data'][0];
        $data = $this->parseHistogramData($histogramData);

        return response()->json($data);
    }

    public function variantStatsCount(CampaignBanner $variant, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->events('banner', $request->get('type'))
                        ->forVariant($variant->uuid)
                        ->get();

        return response()->json($result);
    }

    public function variantStatsHistogram(Campaign $campaign, Stats $stats, Request $request)
    {
        $result = $stats->count()
            ->events('banner', $request->get('type'))
            ->forCampaign($campaign->uuid)
            ->timeHistogram($request->get('interval'))
            ->get();

        if ($result["success"] != true) {
            return response()->json($result);
        }

        $histogramData = $result['data'][0];
        $data = $this->parseHistogramData($histogramData);

        return response()->json($data);
    }






    // public function campaignStartedPayments(Campaign $campaign, Stats $stats)
    // {
    //     $result = $stats->count()
    //                     ->commerce('payment')
    //                     ->forCampaign($campaign->uuid)
    //                     ->get();

    //     return response()->json($result);
    // }

    // public function campaignFinishedPayments(Campaign $campaign, Stats $stats)
    // {
    //     $result = $stats->count()
    //                     ->commerce('purchase')
    //                     ->forCampaign($campaign->uuid)
    //                     ->get();

    //     return response()->json($result);
    // }

    // public function campaignEarned(Campaign $campaign, Stats $stats)
    // {
    //     $result = $stats->sum()
    //         ->commerce('purchase')
    //         ->forCampaign($campaign->uuid)
    //         ->get();

    //     return response()->json($result);
    // }


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
