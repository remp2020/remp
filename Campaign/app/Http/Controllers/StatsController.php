<?php

namespace App\Http\Controllers;

use App\Campaign;
use Carbon\Carbon;
use App\CampaignBanner;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use App\Contracts\Remp\Stats;

class StatsController extends Controller
{
    const DATE_FORMAT = "d/m h:i";

    public $statTypes = [
        "show" => [
            "label" => "Shows",
            "backgroundColor" => "rgb(255, 99, 132)"
        ],
        "click" => [
            "label" => "Clicks",
            "backgroundColor" => "rgb(255, 205, 86)"
        ]
    ];

    public function campaignStatsCount(Campaign $campaign, $type, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->events('banner', $type)
                        ->forCampaign($campaign->uuid)
                        ->from(Carbon::parse($request->get('from')))
                        ->to(Carbon::parse($request->get('to')))
                        ->get();

        if ($result["success"] != true) {
            return response()->json($result);
        }

        return response()->json($result);
    }

    public function variantStatsCount(CampaignBanner $variant, $type, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->events('banner', $type)
                        ->forVariant($variant->uuid)
                        ->from(Carbon::parse($request->get('from')))
                        ->to(Carbon::parse($request->get('to')))
                        ->get();

        if ($result["success"] != true) {
            return response()->json($result);
        }

        if ($request->get('normalized') === "true") {
            $count = $result['data']->count;

            $result['data']->count = $count*($variant->proportion/100);
        }

        return response()->json($result);
    }

    public function calcInterval(Carbon $from, Carbon $to)
    {
        $labels = [];
        $chartWidth = 50;

        $fromTimestamp = $from->timestamp;
        $toTimestamp = $to->timestamp;

        $diff = $to->diffInSeconds($from);
        $interval = $diff / $chartWidth;

        return intval($interval) . "s";
    }

    public function campaignStatsHistogram(Campaign $campaign, Stats $stats, Request $request)
    {
        $from = Carbon::parse($request->get('from'));
        $to = Carbon::parse($request->get('to'));
        $parsedData = [];

        $interval = $this->calcInterval($from, $to);

        foreach ($this->statTypes as $type => $typeData) {
            $parsedData[$type] = [];

            $result = $stats->count()
                            ->events('banner', $type)
                            ->forCampaign($campaign->uuid)
                            ->timeHistogram($interval)
                            ->from($from)
                            ->to($to)
                            ->get();


            if ($result["success"] != true) {
                return response()->json($result);
            }

            $histogramData = $result['data'];

            foreach ($histogramData->time_histogram as $histogramRow) {
                $date = Carbon::parse($histogramRow->time)->format(self::DATE_FORMAT);

                $parsedData[$type][$date] = $histogramRow->value;

                $labels[] = $date;
            }
        }

        $labels = array_unique($labels);

        usort($labels, function ($a, $b) {
            $a = \DateTime::createFromFormat(self::DATE_FORMAT, $a);
            $b = \DateTime::createFromFormat(self::DATE_FORMAT, $b);

            return $a > $b;
        });

        $dataSets = $this->formatDataForChart($parsedData, $labels);

        return response()->json([
            'success' => true,
            'dataSets' => $dataSets,
            'labels' => $labels,
        ]);
    }

    public function formatDataForChart($typesData, $labels)
    {
        $dataSets = [];

        foreach ($typesData as $type => $data) {
            $dataSet = [
                'label' => $this->statTypes[$type]['label'],
                'data' => [],
                'backgroundColor' => $this->statTypes[$type]['backgroundColor']
            ];

            foreach ($labels as $label) {
                if (array_key_exists($label, $data)) {
                    $dataSet['data'][] = $data[$label];
                } else {
                    $dataSet['data'][] = 0;
                }
            }

            $dataSets[] = $dataSet;
        }

        return $dataSets;
    }

    public function campaignPaymentStatsCount(Campaign $campaign, $step, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->commerce($step)
                        ->forCampaign($campaign->uuid)
                        ->get();

        if ($result["success"] != true) {
            return response()->json($result);
        }

        return response()->json($result);
    }

    public function variantPaymentStatsCount(CampaignBanner $variant, $step, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->commerce($step)
                        ->forVariant($variant->uuid)
                        ->get();

        if ($result["success"] != true) {
            return response()->json($result);
        }

        if ($request->get('normalized') === "true") {
            $count = $result['data']->count;

            $result['data']->count = $count * ($variant->proportion / 100);
        }

        return response()->json($result);
    }

    public function campaignPaymentStatsSum(Campaign $campaign, $step, Stats $stats, Request $request)
    {
        $result = $stats->sum()
                        ->commerce($step)
                        ->forCampaign($campaign->uuid)
                        ->get();

        if ($result["success"] != true) {
            return response()->json($result);
        }

        return response()->json($result);
    }

    public function variantPaymentStatsSum(CampaignBanner $variant, $step, Stats $stats, Request $request)
    {
        $result = $stats->sum()
                        ->commerce($step)
                        ->forVariant($variant->uuid)
                        ->get();

        if ($result["success"] != true) {
            return response()->json($result);
        }

        if ($request->get('normalized') === "true") {
            $count = $result['data']->count;

            $result['data']->count = $count * ($variant->proportion / 100);
        }

        return response()->json($result);
    }

    public function variantStatsHistogram(CampaignBanner $variant, Stats $stats, Request $request)
    {
        $from = Carbon::parse($request->get('from'));
        $to = Carbon::parse($request->get('to'));
        $parsedData = [];

        $interval = $this->calcInterval($from, $to);

        foreach ($this->statTypes as $type => $typeData) {
            $parsedData[$type] = [];

            $result = $stats->count()
                            ->events('banner', $type)
                            ->forCampaign($variant->campaign->uuid)
                            ->forVariant($variant->uuid)
                            ->timeHistogram($interval)
                            ->from($from)
                            ->to($to)
                            ->get();

            if ($result["success"] != true) {
                return response()->json($result);
            }

            $histogramData = $result['data'];

            foreach ($histogramData->time_histogram as $histogramRow) {
                $date = Carbon::parse($histogramRow->time)->format(self::DATE_FORMAT);

                $parsedData[$type][$date] = $histogramRow->value;

                $labels[] = $date;
            }
        }

        $labels = array_unique($labels);

        usort($labels, function ($a, $b) {
            $a = \DateTime::createFromFormat(self::DATE_FORMAT, $a);
            $b = \DateTime::createFromFormat(self::DATE_FORMAT, $b);

            return $a > $b;
        });

        $dataSets = $this->formatDataForChart($parsedData, $labels);

        return response()->json([
            'success' => true,
            'dataSets' => $dataSets,
            'labels' => $labels,
        ]);
    }
}
