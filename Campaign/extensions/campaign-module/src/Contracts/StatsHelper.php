<?php

namespace Remp\CampaignModule\Contracts;

use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBanner;
use Remp\CampaignModule\CampaignBannerPurchaseStats;
use Remp\CampaignModule\CampaignBannerStats;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
     * @param Carbon|null $from Expected time in UTC
     * @param Carbon|null $to Expected time in UTC
     *
     * @return array [$campaignStats, $variantsStats]
     */
    public function cachedCampaignAndVariantsStats(Campaign $campaign, Carbon $from = null, Carbon $to = null): array
    {
        /** @var Collection $campaignBanners */
        $campaignBanners = $campaign->campaignBanners()->withTrashed()->get();

        $campaignData = [
            'click_count' => 0,
            'show_count' => 0,
            'payment_count' => 0,
            'purchase_count' => 0,
            'purchase_sums' => [],
            'ctr' => 0,
            'conversions' => 0,
        ];

        $variantsData = [];
        foreach ($campaignBanners as $campaignBanner) {
            $variantsData[$campaignBanner->uuid] = $campaignData;
        }

        $statsQuerySelect = 'campaign_banner_id, '.
            'SUM(click_count) AS click_count, '.
            'SUM(show_count) as show_count, '.
            'SUM(payment_count) as payment_count, '.
            'SUM(purchase_count) as purchase_count';

        $statsQuery = CampaignBannerStats::select(DB::raw($statsQuerySelect))
            ->whereIn('campaign_banner_id', $campaignBanners->pluck('id'))
            ->groupBy('campaign_banner_id');

        $purchaseStatsQuerySelect = 'campaign_banner_id, '.
            'SUM(`sum`) AS purchase_sum, '.
            'currency';

        $purchaseStatsQuery = CampaignBannerPurchaseStats::select(DB::raw($purchaseStatsQuerySelect))
            ->whereIn('campaign_banner_id', $campaignBanners->pluck('id'))
            ->groupBy(['campaign_banner_id', 'currency']);

        if ($from) {
            $statsQuery->where('time_from', '>=', $from);
            $purchaseStatsQuery->where('time_from', '>=', $from);
        }
        if ($to) {
            $statsQuery->where('time_to', '<=', $to);
            $purchaseStatsQuery->where('time_to', '<=', $to);
        }

        /** @var CampaignBannerStats $stat */
        foreach ($statsQuery->get() as $stat) {
            // Campaign banner may already be soft-deleted
            $statCampaignBanner = $stat->campaignBanner()->withTrashed()->first();

            $variantData = $variantsData[$statCampaignBanner->uuid];
            $variantData['click_count'] = (int) $stat->click_count;
            $variantData['show_count'] = (int) $stat->show_count;
            $variantData['payment_count'] = (int) $stat->payment_count;
            $variantData['purchase_count'] = (int) $stat->purchase_count;

            $variantsData[$statCampaignBanner->uuid] = StatsHelper::addCalculatedValues($variantData);

            $campaignData['click_count'] += $variantData['click_count'];
            $campaignData['show_count'] += $variantData['show_count'];
            $campaignData['payment_count'] += $variantData['payment_count'];
            $campaignData['purchase_count'] += $variantData['purchase_count'];
        }
        $campaignData = StatsHelper::addCalculatedValues($campaignData);

        foreach ($purchaseStatsQuery->get() as $stat) {
            // Campaign banner may already be soft-deleted
            $statCampaignBanner = $stat->campaignBanner()->withTrashed()->first();

            if (!array_key_exists($statCampaignBanner->uuid, $variantsData)) {
                throw new \LogicException("Campaign banner {$statCampaignBanner->uuid} has aggregated purchases without other aggregated attributes.");
            }

            if (!array_key_exists($stat->currency, $variantsData[$statCampaignBanner->uuid]['purchase_sums'])) {
                $variantsData[$statCampaignBanner->uuid]['purchase_sums'][$stat->currency] = 0.0;
            }
            $variantsData[$statCampaignBanner->uuid]['purchase_sums'][$stat->currency] += (double) $stat['purchase_sum'];

            if (!array_key_exists($stat->currency, $campaignData['purchase_sums'])) {
                $campaignData['purchase_sums'][$stat->currency] = 0.0;
            }
            $campaignData['purchase_sums'][$stat->currency] += (double) $stat['purchase_sum'];
        }

        return [$campaignData, $variantsData];
    }

    /**
     * @param Campaign    $campaign
     * @param Carbon|null $from Expected time in UTC
     * @param Carbon|null $to Expected time in UTC
     *
     * @return array
     */
    public function campaignStats(Campaign $campaign, Carbon $from = null, Carbon $to = null)
    {
        return $this->variantsStats($campaign->variants_uuids, $from, $to);
    }

    /**
     * @param CampaignBanner $variant
     * @param Carbon|null    $from Expected time in UTC
     * @param Carbon|null    $to Expected time in UTC
     *
     * @return array
     */
    public function variantStats(CampaignBanner $variant, Carbon $from = null, Carbon $to = null)
    {
        return $this->variantsStats([$variant->uuid], $from, $to);
    }

    public function addCalculatedValues($data)
    {
        // calculate ctr & conversions
        if (isset($data['show_count']) && $data['show_count'] > 0) {
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

        return $r->get();
    }
}
