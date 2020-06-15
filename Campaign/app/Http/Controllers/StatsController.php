<?php

namespace App\Http\Controllers;

use App\Campaign;
use App\CampaignBanner;
use App\CampaignBannerStats;
use App\Contracts\StatsContract;
use App\Contracts\StatsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Remp\MultiArmedBandit\Lever;
use Remp\MultiArmedBandit\Machine;

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

        // round values if interval is bigger than 1 hour
        if ($from->diffInMinutes($to) >= 3600) {
            $from->minute(0)->second(0);
            $nextTo = (clone $to)->minute(0)->second(0);
            if ($nextTo->ne($to)) {
                $to = $nextTo->addHour();
            }
        }

        $utcFrom = (clone $from)->tz('UTC');
        $utcTo = (clone $to)->tz('UTC');
        [$campaignData, $variantsData] = $this->statsHelper->cachedCampaignAndVariantsStats($campaign, $utcFrom, $utcTo);
        $campaignData['histogram'] = $this->getHistogramData($campaign->variants_uuids, $from, $to);

        foreach ($variantsData as $uuid => $variantData) {
            $variantsData[$uuid]['histogram'] = $this->getHistogramData([$uuid], $from, $to);
        }

        // a/b test evaluation data
        foreach ($this->getVariantProbabilities($variantsData, 'click_count') as $variantId => $probability) {
            $variantsData[$variantId]['click_probability'] = $probability;
        }

        foreach ($this->getVariantProbabilities($variantsData, 'purchase_count') as $variantId => $probability) {
            $variantsData[$variantId]['purchase_probability'] = $probability;
        }

        return [
            'campaign' => $campaignData,
            'variants' => $variantsData,
        ];
    }

    private function getHistogramData(array $variantUuids, Carbon $from, Carbon $to)
    {
        $parsedData = [];
        $labels = [];

        [$interval, $chartJsTimeUnit] = $this->calcInterval($from, $to);

        foreach ($this->statTypes as $type => $typeData) {
            $parsedData[$type] = [];

            $data = $this->stats->count()
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
            'timeUnit' => $chartJsTimeUnit
        ];
    }

    private function getVariantProbabilities($variantsData, $conversionField)
    {
        $machine = new Machine(1000);
        $zeroStat = [];
        foreach ($variantsData as $variantId => $data) {
            if ($data['show_count'] === 0 || !$data[$conversionField]) {
                $zeroStat[$variantId] = 0;
                continue;
            }
            $machine->addLever(new Lever($variantId, $data[$conversionField], $data['show_count']));
        }
        return $machine->run() + $zeroStat;
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

    private function calcInterval(Carbon $from, Carbon $to): array
    {
        $diff = $to->diffInSeconds($from);

        if ($diff > 31*24*3600) {
            return ['168h', 'week'];
        }
        if ($diff > 4*24*3600) {
            return ['24h', 'day'];
        }
        if ($diff > 2*3600) {
            return ['3600s', 'hour'];
        }
        if ($diff > 1800) {
            return ['900s', 'minute'];
        }
        return ['300s', 'minute'];
    }
}
