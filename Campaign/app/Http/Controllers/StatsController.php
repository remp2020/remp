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

        $from->minute(0)->second(0);
        $nextTo = (clone $to)->minute(0)->second(0);
        if ($nextTo->ne($to)) {
            $to = $nextTo->addHour();
        }

        $chartWidth = $request->get('chartWidth');

        $select = 'campaign_banner_id, '.
            'SUM(click_count) AS click_count, '.
            'SUM(show_count) as show_count, '.
            'SUM(payment_count) as payment_count, '.
            'SUM(purchase_sum) as purchase_sum, '.
            'COALESCE(GROUP_CONCAT(DISTINCT(purchase_currency) SEPARATOR \',\'),\'\') as purchase_currency';

        $campaignBannerIds = $campaign->campaignBanners()->withTrashed()->get()->pluck('id');

        $campaignData = [
            'click_count' => 0,
            'show_count' => 0,
            'payment_count' => 0,
            'purchase_count' => 0,
            'purchase_sum' => 0.0,
            'purchase_currency' => ''
        ];

        foreach ($campaignBannerIds as $id) {
            $variantsData[$id] = $campaignData;
        }

        $stats = CampaignBannerStats::select(DB::raw($select))
            ->whereIn('campaign_banner_id', $campaignBannerIds)
            ->where('time_from', '>=', $from)
            ->where('time_to', '<=', $to)
            ->groupBy('campaign_banner_id')
            ->get();

        foreach ($stats as $stat) {
            $variantData['click_count'] = (int) $stat->click_count;
            $variantData['show_count'] = (int) $stat->show_count;
            $variantData['payment_count'] = (int) $stat->payment_count;
            $variantData['purchase_count'] = (int) $stat->purchase_count;
            $variantData['purchase_sum'] = (double) $stat->purchase_sum;
            $variantData['purchase_currency'] = explode(',', $stat->purchase_currency)[0]; // Currently supporting only one currency

            $variantsData[$stat->campaign_banner_id] = StatsHelper::addCalculatedValues($variantData);

            $campaignData['click_count'] += $variantData['click_count'];
            $campaignData['show_count'] += $variantData['show_count'];
            $campaignData['payment_count'] += $variantData['payment_count'];
            $campaignData['purchase_count'] += $variantData['purchase_count'];
            $campaignData['purchase_sum'] += $variantData['purchase_sum'];

            if ($variantData['purchase_currency'] !== '') {
                $campaignData['purchase_currency'] = $variantData['purchase_currency'];
            }
        }

        $campaignData['histogram'] = $this->getHistogramData($campaign->variants_uuids, $from, $to, $chartWidth);

        foreach ($variantsData as $campaignBannerId => $variantData) {
            $uuid = CampaignBanner::find($campaignBannerId)->uuid;
            $variantsData[$campaignBannerId]['histogram'] = $this->getHistogramData([$uuid], $from, $to, $chartWidth);
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

    private function getHistogramData(array $variantUuids, Carbon $from, Carbon $to, $chartWidth)
    {
        if ($chartWidth == 0) {
            return [
                'dataSets' => [],
                'labels' => [],
            ];
        }

        $parsedData = [];
        $labels = [];

        $interval = $this->calcInterval($from, $to, $chartWidth);

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

    private function calcInterval(Carbon $from, Carbon $to, $chartWidth)
    {
        $numOfCols = (int)($chartWidth / 40);

        $diff = $to->diffInSeconds($from);
        $interval = $diff / $numOfCols;

        return (int)$interval . 's';
    }
}
