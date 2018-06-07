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
        'Shows' => 'show',
        'Clicks' => 'click',
    ];

    public function campaignStatsCount(Campaign $campaign, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->events('banner', $request->get('type'))
                        ->forCampaign($campaign->uuid)
                        ->get();

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

        return $interval . "s";
    }

    public function campaignStatsHistogram(Campaign $campaign, Stats $stats, Request $request)
    {
        $from = Carbon::parse($request->get('from'));
        $to = Carbon::parse($request->get('to'));
        $onlyType = $request->get('type');
        $parsedData = [];

        $interval = $this->calcInterval($from, $to);

        foreach ($this->statTypes as $title => $type) {
            if ($onlyType && $onlyType !== $type) continue;

            $parsedData[$type] = [];

            $result = $stats->count()
                            ->events('banner', $type)
                            ->forCampaign($campaign->uuid)
                            ->timeHistogram($interval)
                            ->from($from)
                            ->to($to)
                            ->get();


            if ($result["success"] != true || $result['data']->count === 0) {
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
            'dataSets' => $dataSets,
            'labels' => $labels,
        ]);
    }

    public function formatDataForChart($typesData, $labels)
    {
        $dataSets = [];

        foreach ($typesData as $type => $data) {
            $dataSet = [
                'label' => $type,
                'data' => [],
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

        $histogramData = $result['data'];
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

    public function variantStatsHistogram(CampaignBanner $variant, Stats $stats, Request $request)
    {
        $onlyType = $request->get('type');
        $parsedData = [];
        $labels = [];

        foreach ($this->statTypes as $title => $type) {
            if ($onlyType && $onlyType !== $type) continue;

            $parsedData[$type] = [];

            $result = $stats->count()
                            ->events('banner', $type)
                            ->forCampaign($variant->campaign->uuid)
                            ->forVariant($variant->uuid)
                            ->timeHistogram($request->get('interval'))
                            ->get();

            if ($result["success"] != true || $result['data']->count === 0) {
                return response()->json($result);
            }

            $histogramData = $result['data'];

            foreach ($histogramData->time_histogram as $histogramRow) {
                $date = (new \DateTime($histogramRow->time))->format(self::DATE_FORMAT);

                $parsedData[$type][$date] = $histogramRow->value;
            }
        }

        dd($parsedData);

        $labels = array_unique($labels);
        $dataSets = $this->formatDataForChart($parsedData, $labels);

        return response()->json([
            'dataSets' => $dataSets,
            'labels' => $labels,
        ]);
    }
}
