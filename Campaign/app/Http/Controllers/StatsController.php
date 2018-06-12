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

        return $result;
    }

    public function variantStatsCount(CampaignBanner $variant, $type, Stats $stats, Request $request, $normalized = false)
    {
        $result = $stats->count()
                        ->events('banner', $type)
                        ->forVariant($variant->uuid)
                        ->from(Carbon::parse($request->get('from')))
                        ->to(Carbon::parse($request->get('to')))
                        ->get();

        if ($result["success"] != true) {
            return $result;
        }

        if ($normalized) {
            $count = $result['data']->count;

            $result['data']->count = $count*($variant->proportion/100);
        }

        return $result;
    }

    public function campaignPaymentStatsCount(Campaign $campaign, $step, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->commerce($step)
                        ->forCampaign($campaign->uuid)
                        ->get();

        return $result;
    }

    public function campaignPaymentStatsSum(Campaign $campaign, $step, Stats $stats, Request $request)
    {
        $result = $stats->sum()
                        ->commerce($step)
                        ->forCampaign($campaign->uuid)
                        ->get();

        return $result;
    }

    public function campaignStatsHistogram(Campaign $campaign, Stats $stats, Request $request)
    {
        return $this->getHistogramData($stats, $request, $campaign->uuid);
    }

    public function variantPaymentStatsCount(CampaignBanner $variant, $step, Stats $stats, Request $request, $normalized = false)
    {
        $result = $stats->count()
            ->commerce($step)
            ->forVariant($variant->uuid)
            ->get();

        if ($result["success"] != true) {
            return $result;
        }

        if ($normalized) {
            $count = $result['data']->count;

            $result['data']->count = $count * ($variant->proportion / 100);
        }

        return $result;
    }

    public function variantPaymentStatsSum(CampaignBanner $variant, $step, Stats $stats, Request $request, $normalized = false)
    {
        $result = $stats->sum()
                        ->commerce($step)
                        ->forVariant($variant->uuid)
                        ->get();

        if ($result["success"] != true) {
            return $result;
        }

        if ($normalized) {
            $count = $result['data']->count;

            $result['data']->count = $count * ($variant->proportion / 100);
        }

        return $result;
    }

    public function variantStatsHistogram(CampaignBanner $variant, Stats $stats, Request $request)
    {
        return $this->getHistogramData($stats, $request, $variant->campaign->uuid, $variant->uuid);
    }

    protected function getHistogramData(Stats $stats, Request $request, $campaignUuid, $variantUuid = null)
    {
        $from = Carbon::parse($request->get('from'));
        $to = Carbon::parse($request->get('to'));
        $chartWidth = $request->get('chartWidth');
        $parsedData = [];

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

            if ($result["success"] != true) {
                return $result;
            }

            $histogramData = $result['data'];

            foreach ($histogramData->time_histogram as $histogramRow) {
                $date = Carbon::parse($histogramRow->time)->toRfc3339String();

                $parsedData[$type][$date] = $histogramRow->value;

                $labels[] = $date;
            }
        }

        $labels = array_unique($labels);

        usort($labels, function ($a, $b) {
            $a = \DateTime::createFromFormat(Carbon::RFC3339, $a);
            $b = \DateTime::createFromFormat(Carbon::RFC3339, $b);

            return $a > $b;
        });

        $dataSets = $this->formatDataForChart($parsedData, $labels);

        return [
            'success' => true,
            'dataSets' => $dataSets,
            'labels' => $labels,
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
            'click_count_normalized' => $this->variantStatsCount($variant, 'click', $stats, $request, true),
            'show_count' => $this->variantStatsCount($variant, 'show', $stats, $request),
            'show_count_normalized' => $this->variantStatsCount($variant, 'show', $stats, $request, true),
            'payment_count' => $this->variantPaymentStatsCount($variant, 'payment', $stats, $request),
            'payment_count_normalized' => $this->variantPaymentStatsCount($variant, 'payment', $stats, $request, true),
            'purchase_count' => $this->variantPaymentStatsCount($variant, 'purchase', $stats, $request),
            'purchase_count_normalized' => $this->variantPaymentStatsCount($variant, 'purchase', $stats, $request, true),
            'purchase_sum' => $this->variantPaymentStatsSum($variant, 'purchase', $stats, $request),
            'purchase_sum_normalized' => $this->variantPaymentStatsSum($variant, 'purchase', $stats, $request, true),
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

    protected function calcInterval(Carbon $from, Carbon $to, $chartWidth)
    {
        $labels = [];
        $numOfCols = intval($chartWidth / 20);

        $fromTimestamp = $from->timestamp;
        $toTimestamp = $to->timestamp;

        $diff = $to->diffInSeconds($from);
        $interval = $diff / $numOfCols;

        return intval($interval) . "s";
    }
}
