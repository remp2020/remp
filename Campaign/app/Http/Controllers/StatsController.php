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
    public $statTypes = [
        "show" => [
            "label" => "Shows",
            "backgroundColor" => "#2196f3"
        ],
        "click" => [
            "label" => "Clicks",
            "backgroundColor" => "#009688"
        ]
    ];

    public function campaignStatsCount(Campaign $campaign, $type, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->events('banner', $type)
                        ->forCampaign($campaign->uuid)
                        ->from(Carbon::parse($request->get('from'), $request->input('tz')))
                        ->to(Carbon::parse($request->get('to'), $request->input('tz')))
                        ->get();

        return $result[0];
    }

    public function variantStatsCount(CampaignBanner $variant, $type, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->events('banner', $type)
                        ->forVariant($variant->uuid)
                        ->from(Carbon::parse($request->get('from'), $request->input('tz')))
                        ->to(Carbon::parse($request->get('to'), $request->input('tz')))
                        ->get();

        $result = $result[0];

        return $result;
    }

    public function campaignPaymentStatsCount(Campaign $campaign, $step, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->commerce($step)
                        ->from(Carbon::parse($request->get('from'), $request->input('tz')))
                        ->to(Carbon::parse($request->get('to'), $request->input('tz')))
                        ->forCampaign($campaign->uuid)
                        ->get();

        return $result[0];
    }

    public function campaignPaymentStatsSum(Campaign $campaign, $step, Stats $stats, Request $request)
    {
        $result = $stats->sum()
                        ->commerce($step)
                        ->from(Carbon::parse($request->get('from'), $request->input('tz')))
                        ->to(Carbon::parse($request->get('to'), $request->input('tz')))
                        ->forCampaign($campaign->uuid)
                        ->get();

        return $result[0];
    }

    public function campaignStatsHistogram(Campaign $campaign, Stats $stats, Request $request)
    {
        return $this->getHistogramData($stats, $request, $campaign->uuid);
    }

    public function variantPaymentStatsCount(CampaignBanner $variant, $step, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->commerce($step)
                        ->from(Carbon::parse($request->get('from'), $request->input('tz')))
                        ->to(Carbon::parse($request->get('to'), $request->input('tz')))
                        ->forVariant($variant->uuid)
                        ->get();

        $result = $result[0];

        return $result;
    }

    public function variantPaymentStatsSum(CampaignBanner $variant, $step, Stats $stats, Request $request)
    {
        $result = $stats->sum()
                        ->commerce($step)
                        ->from(Carbon::parse($request->get('from'), $request->input('tz')))
                        ->to(Carbon::parse($request->get('to'), $request->input('tz')))
                        ->forVariant($variant->uuid)
                        ->get();

        $result = $result[0];

        return $result;
    }

    public function variantStatsHistogram(CampaignBanner $variant, Stats $stats, Request $request)
    {
        return $this->getHistogramData($stats, $request, $variant->campaign->uuid, $variant->uuid);
    }

    protected function getHistogramData(Stats $stats, Request $request, $campaignUuid, $variantUuid = null)
    {
        $from = Carbon::parse($request->get('from'), $request->input('tz'));
        $to = Carbon::parse($request->get('to'), $request->input('tz'));
        $chartWidth = $request->get('chartWidth');
        $parsedData = [];
        $labels = [];

        $interval = $this->calcInterval($from, $to, $chartWidth);

        foreach ($this->statTypes as $type => $typeData) {
            $parsedData[$type] = [];

            $stats = $stats->count()
                        ->events('banner', $type)
                        ->forCampaign($campaignUuid)
                        ->timeHistogram($interval)
                        ->from($from)
                        ->to($to);

            if ($variantUuid) {
                $stats->forVariant($variantUuid);
            }

            $result = $stats->get();

            $histogramData = $result[0];

            foreach ($histogramData->time_histogram as $histogramRow) {
                $date = Carbon::parse($histogramRow->time);

                $parsedData[$type][$date->toRfc3339String()] = $histogramRow->value;

                $labels[] = $date;
            }
        }

        $labels = array_unique($labels);

        usort($labels, function ($a, $b) {
            return $a > $b;
        });

        list($dataSets, $formattedLabels) = $this->formatDataForChart($parsedData, $labels);

        return [
            'dataSets' => $dataSets,
            'labels' => $formattedLabels,
        ];
    }

    public function campaignStats(Campaign $campaign, Request $request, Stats $stats)
    {
        return [
            'click_count' => $this->campaignStatsCount($campaign, 'click', $stats, $request),
            'payment_count' => $this->campaignPaymentStatsCount($campaign, 'payment', $stats, $request),
            'purchase_count' => $this->campaignPaymentStatsCount($campaign, 'purchase', $stats, $request),
            'purchase_sum' => $this->campaignPaymentStatsSum($campaign, 'purchase', $stats, $request),
            'histogram' => $this->campaignStatsHistogram($campaign, $stats, $request),
        ];
    }

    public function variantStats(CampaignBanner $variant, Request $request, Stats $stats)
    {
        return [
            'click_count' => $this->variantStatsCount($variant, 'click', $stats, $request),
            'show_count' => $this->variantStatsCount($variant, 'show', $stats, $request),
            'payment_count' => $this->variantPaymentStatsCount($variant, 'payment', $stats, $request),
            'purchase_count' => $this->variantPaymentStatsCount($variant, 'purchase', $stats, $request),
            'purchase_sum' => $this->variantPaymentStatsSum($variant, 'purchase', $stats, $request),
            'histogram' => $this->variantStatsHistogram($variant, $stats, $request),
        ];
    }

    protected function formatDataForChart($typesData, $labels)
    {
        $dataSets = [];

        foreach ($typesData as $type => $data) {
            $dataSet = [
                'label' => $this->statTypes[$type]['label'],
                'data' => [],
                'backgroundColor' => $this->statTypes[$type]['backgroundColor']
            ];

            foreach ($labels as $i => $label) {
                $labelStr = is_string($label) ? $label : $label->toRfc3339String();

                if (array_key_exists($labelStr, $data)) {
                    $dataSet['data'][] = $data[$labelStr];
                } else {
                    $dataSet['data'][] = 0;
                }

                $labels[$i] = $labelStr;
            }

            $dataSets[] = $dataSet;
        }

        return [
            $dataSets,
            $labels,
        ];
    }

    protected function calcInterval(Carbon $from, Carbon $to, $chartWidth)
    {
        $numOfCols = intval($chartWidth / 40);

        $diff = $to->diffInSeconds($from);
        $interval = $diff / $numOfCols;

        return intval($interval) . "s";
    }
}
