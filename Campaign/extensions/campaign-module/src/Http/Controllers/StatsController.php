<?php

namespace Remp\CampaignModule\Http\Controllers;

use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\Contracts\StatsContract;
use Remp\CampaignModule\Contracts\StatsHelper;
use Remp\CampaignModule\Models\Interval\IntervalModeEnum;
use Remp\CampaignModule\Rules\ValidCarbonDate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
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
        $request->validate([
            'from' => ['sometimes', new ValidCarbonDate],
            'to' => ['sometimes', new ValidCarbonDate],
            'interval_mode' => ['sometimes', Rule::enum(IntervalModeEnum::class)],
        ]);

        $tz = $request->input('tz');
        $intervalMode = IntervalModeEnum::from($request->input('interval_mode', 'auto'));

        $from = Carbon::parse($request->get('from'), $tz);
        $to = Carbon::parse($request->get('to'), $tz);

        // round values if interval is bigger than 1 hour
        if ($from->diffInMinutes($to) >= 3600) {
            $from->minute(0)->second(0);
            $nextTo = (clone $to)->minute(0)->second(0);
            if ($nextTo->ne($to)) {
                $to = $nextTo->addHour();
            }
        }

        [$campaignData, $variantsData] = $this->statsHelper->cachedCampaignAndVariantsStats($campaign, $from, $to);
        $campaignData['histogram'] = $this->getHistogramData($campaign->variants_uuids, $from, $to, $tz, $intervalMode);

        foreach ($variantsData as $uuid => $variantData) {
            $variantsData[$uuid]['histogram'] = $this->getHistogramData([$uuid], $from, $to, $tz, $intervalMode);
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

    private function getHistogramData(array $variantUuids, Carbon $from, Carbon $to, string $tz, IntervalModeEnum $intervalMode = IntervalModeEnum::Auto)
    {
        $parsedData = [];
        $labels = [];

        $intervalConfig = $this->statsHelper->calcInterval($from, $to, $intervalMode);

        foreach ($this->statTypes as $type => $typeData) {
            $parsedData[$type] = [];

            $data = $this->stats->count()
                ->forVariants($variantUuids)
                ->timeHistogram($intervalConfig->interval, $tz)
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
                $date = Carbon::parse($histogramRow->time)->setTimezone('UTC');
                $parsedData[$type][$date->toRfc3339String()] = $histogramRow->value;
                $labels[] = $date->toRfc3339String();
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
            'timeUnit' => $intervalConfig->timeUnit,
            'stepSize' => $intervalConfig->stepSize
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
}
