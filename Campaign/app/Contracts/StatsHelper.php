<?php

namespace App\Contracts;

use App\Campaign;
use App\CampaignBanner;
use App\CampaignBannerStats;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatsHelper
{
    private $stats;

    public function __construct(StatsContract $statsContract)
    {
        $this->stats = $statsContract;
    }


    /**
     *
     * @param Campaign    $campaign
     * @param Carbon|null $from
     * @param Carbon|null $to
     *
     * @return array [$campaignStats, $variantsStats]
     */
    public function cachedCampaignAndVariantsStats(Campaign $campaign, Carbon $from = null, Carbon $to = null): array
    {
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

        $q = CampaignBannerStats::select(DB::raw($select))
            ->whereIn('campaign_banner_id', $campaignBannerIds)
            ->groupBy('campaign_banner_id');

        if ($from) {
            $q->where('time_from', '>=', $from);
        }
        if ($to) {
            $q->where('time_to', '<=', $to);
        }

        foreach ($q->get() as $stat) {
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

        $campaignData = StatsHelper::addCalculatedValues($campaignData);

        return [$campaignData, $variantsData];
    }

    public function campaignStats(Campaign $campaign, Carbon $from = null, Carbon $to = null)
    {
        return $this->variantsStats($campaign->variants_uuids, $from, $to);
    }

    public function variantStats(CampaignBanner $variant, Carbon $from = null, Carbon $to = null)
    {
        return $this->variantsStats([$variant->uuid], $from, $to);
    }

    public function addCalculatedValues($data)
    {
        $data['ctr'] = 0;
        $data['conversions'] = 0;

        // calculate ctr & conversions
        if (isset($data['show_count'])) {
            if ($data['click_count']) {
                $data['ctr'] = ($data['click_count'] / $data['show_count']) * 100;
            }

            if ($data['purchase_count']) {
                $data['conversions'] = ($data['purchase_count'] / $data['show_count']) * 100;
            }
        }
        return $data;
    }

    private function variantsStats($variantUuids, Carbon $from = null, Carbon $to = null)
    {
        $data = [
            'click_count' => $this->campaignStatsCount($variantUuids, 'click', $from, $to),
            'show_count' => $this->campaignStatsCount($variantUuids, 'show', $from, $to),
            'payment_count' => $this->campaignPaymentStatsCount($variantUuids, 'payment', $from, $to),
            'purchase_count' => $this->campaignPaymentStatsCount($variantUuids, 'purchase', $from, $to),
            'purchase_sum' => $this->campaignPaymentStatsSum($variantUuids, 'purchase', $from, $to),
        ];

        return $data;
    }

    private function campaignStatsCount($variantUuids, $type, Carbon $from = null, Carbon $to = null)
    {
        $r = $this->stats->count()
            ->events('banner', $type)
            ->forVariants($variantUuids);

        if ($from) {
            $r->from($from);
        }
        if ($to) {
            $r->to($to);
        }

        return $r->get()[0];
    }

    private function campaignPaymentStatsCount($variantUuids, $step, Carbon $from = null, Carbon $to = null)
    {
        $r = $this->stats->count()
            ->commerce($step)
            ->forVariants($variantUuids);

        if ($from) {
            $r->from($from);
        }
        if ($to) {
            $r->to($to);
        }

        return $r->get()[0];
    }

    private function campaignPaymentStatsSum($variantUuids, $step, Carbon $from = null, Carbon $to = null)
    {
        $r = $this->stats->sum()
            ->commerce($step)
            ->forVariants($variantUuids)
            ->groupBy('currency');

        if ($from) {
            $r->from($from);
        }
        if ($to) {
            $r->to($to);
        }

        // TODO currently support only 1 currency
        return $r->get()[0];
    }
}
