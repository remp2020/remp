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

    public function campaignStatsCount($variantUuids, $type, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->events('banner', $type)
                        ->forVariants($variantUuids)
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

        return $result[0];
    }

    public function campaignPaymentStatsCount($variantUuids, $step, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->commerce($step)
                        ->forVariants($variantUuids)
                        ->from(Carbon::parse($request->get('from'), $request->input('tz')))
                        ->to(Carbon::parse($request->get('to'), $request->input('tz')))
                        ->get();

        return $result[0];
    }

    public function campaignPaymentStatsSum($variantUuids, $step, Stats $stats, Request $request)
    {
        $result = $stats->sum()
                        ->commerce($step)
                        ->forVariants($variantUuids)
                        ->from(Carbon::parse($request->get('from'), $request->input('tz')))
                        ->to(Carbon::parse($request->get('to'), $request->input('tz')))
                        ->get();

        return $result[0];
    }

    public function campaignStatsHistogram($variantUuids, Stats $stats, Request $request)
    {
        return $this->getHistogramData($stats, $request, $variantUuids);
    }

    public function variantPaymentStatsCount(CampaignBanner $variant, $step, Stats $stats, Request $request)
    {
        $result = $stats->count()
                        ->commerce($step)
                        ->from(Carbon::parse($request->get('from'), $request->input('tz')))
                        ->to(Carbon::parse($request->get('to'), $request->input('tz')))
                        ->forVariant($variant->uuid)
                        ->get();

        return $result[0];
    }

    public function variantPaymentStatsSum(CampaignBanner $variant, $step, Stats $stats, Request $request)
    {
        $result = $stats->sum()
                        ->commerce($step)
                        ->from(Carbon::parse($request->get('from'), $request->input('tz')))
                        ->to(Carbon::parse($request->get('to'), $request->input('tz')))
                        ->forVariant($variant->uuid)
                        ->get();

        return $result[0];
    }

    public function variantStatsHistogram(CampaignBanner $variant, Stats $stats, Request $request)
    {
        return $this->getHistogramData($stats, $request, [$variant->uuid]);
    }

    protected function getHistogramData(Stats $stats, Request $request, $variantUuids)
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
                        ->forVariants($variantUuids)
                        ->timeHistogram($interval)
                        ->from($from)
                        ->to($to);

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

    public function getStats(Campaign $campaign, Request $request, Stats $stats)
    {
        $campaignData = $this->campaignStats($campaign, $request, $stats);

        $variantsData = [];
        foreach ($campaign->campaignBanners()->withTrashed()->get() as $variant) {
            $variantsData[$variant->id] = $this->variantStats($variant, $request, $stats);
        }

        return [
            'campaign' => $campaignData,
            'variants' => $variantsData,
        ];
    }

    public function campaignStats(Campaign $campaign, Request $request, Stats $stats)
    {
        $variantUuids = $campaign->campaignBanners()->withTrashed()->get()->map(function ($banner) {
            return $banner["uuid"];
        })->toArray();

        $data = [
            'click_count' => $this->campaignStatsCount($variantUuids, 'click', $stats, $request),
            'show_count' => $this->campaignStatsCount($variantUuids, 'show', $stats, $request),
            'payment_count' => $this->campaignPaymentStatsCount($variantUuids, 'payment', $stats, $request),
            'purchase_count' => $this->campaignPaymentStatsCount($variantUuids, 'purchase', $stats, $request),
            'purchase_sum' => $this->campaignPaymentStatsSum($variantUuids, 'purchase', $stats, $request),
            'histogram' => $this->campaignStatsHistogram($variantUuids, $stats, $request),
        ];

        return $this->addCalculatedValues($data);
    }

    public function variantStats(CampaignBanner $variant, Request $request, Stats $stats)
    {
        $data = [
            'click_count' => $this->variantStatsCount($variant, 'click', $stats, $request),
            'show_count' => $this->variantStatsCount($variant, 'show', $stats, $request),
            'payment_count' => $this->variantPaymentStatsCount($variant, 'payment', $stats, $request),
            'purchase_count' => $this->variantPaymentStatsCount($variant, 'purchase', $stats, $request),
            'purchase_sum' => $this->variantPaymentStatsSum($variant, 'purchase', $stats, $request),
            'histogram' => $this->variantStatsHistogram($variant, $stats, $request),
        ];

        return $this->addCalculatedValues($data);
    }

    public function addCalculatedValues($data)
    {
        $data['ctr'] = 0;
        $data['conversions'] = 0;

        // calculate ctr & conversions
        if ($data['show_count']->count) {
            if ($data['click_count']->count) {
                $data['ctr'] = ($data['click_count']->count / $data['show_count']->count) * 100;
            }

            if ($data['purchase_count']->count) {
                $data['conversions'] = ($data['purchase_count']->count / $data['show_count']->count) * 100;
            }
        }

        return $data;
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
