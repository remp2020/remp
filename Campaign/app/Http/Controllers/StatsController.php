<?php

namespace App\Http\Controllers;

use App\Campaign;
use App\Contracts\StatsContract;
use App\Contracts\StatsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatsController extends Controller
{
    private $statTypes = [
        "show" => [
            "label" => "Shows",
            "backgroundColor" => "#E63952",
        ],
        "click" => [
            "label" => "Clicks",
            "backgroundColor" => "#00C7DF",
        ],
        "commerce" => [
            "label" => "Conversions",
            "backgroundColor" => "#FFC34A",
        ],
    ];

    private $statsHelper;

    private $stats;

    public function __construct(StatsHelper $statsHelper, StatsContract $stats)
    {
        $this->statsHelper = $statsHelper;
        $this->stats = $stats;
    }

    public function getStats(Campaign $campaign, Request $request)
    {
        $from = Carbon::parse($request->get('from'), $request->input('tz'));
        $to = Carbon::parse($request->get('to'), $request->input('tz'));
        $chartWidth = $request->get('chartWidth');

        $campaignData = $this->statsHelper->campaignStats($campaign, $from, $to);
        $campaignData['histogram'] = $this->getHistogramData($campaign->variants_uuids, $from, $to, $chartWidth);

        $variantsData = [];
        foreach ($campaign->campaignBanners()->withTrashed()->get() as $variant) {
            $variantStats = $this->statsHelper->variantStats($variant, $from, $to);
            $variantStats['histogram'] = $this->getHistogramData([$variant->uuid], $from, $to, $chartWidth);
            $variantsData[$variant->id] = $variantStats;
        }

        return [
            'campaign' => $campaignData,
            'variants' => $variantsData,
        ];
    }

    private function getHistogramData(array $variantUuids, Carbon $from, Carbon $to, $chartWidth)
    {
        $parsedData = [];
        $labels = [];

        $interval = $this->calcInterval($from, $to, $chartWidth);

        foreach ($this->statTypes as $type => $typeData) {
            $parsedData[$type] = [];

            $data = $stats->count()
                ->forVariants($variantUuids)
                ->timeHistogram($interval)
                ->from($from)
                ->to($to);

            if ($type === 'commerce') {
                $data = $data->commerce('purchase');
            } else {
                $data = $data->events('banner', $type);
            }

            $result = $data->get();

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

        [$dataSets, $formattedLabels] = $this->formatDataForChart($parsedData, $labels);

        return [
            'dataSets' => $dataSets,
            'labels' => $formattedLabels,
        ];
    }

    private function formatDataForChart($typesData, $labels)
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

    private function calcInterval(Carbon $from, Carbon $to, $chartWidth)
    {
        $numOfCols = intval($chartWidth / 40);

        $diff = $to->diffInSeconds($from);
        $interval = $diff / $numOfCols;

        return intval($interval) . "s";
    }
}
