<?php

namespace App\Contracts;

use App\Campaign;
use App\CampaignBanner;
use Illuminate\Support\Carbon;

class StatsHelper
{
    private $stats;

    public function __construct(StatsContract $statsContract)
    {
        $this->stats = $statsContract;
    }

    public function campaignStats(Campaign $campaign, Carbon $from = null, Carbon $to = null)
    {
        return $this->variantsStats($campaign->variants_uuids, $from, $to);
    }

    public function variantStats(CampaignBanner $variant, Carbon $from = null, Carbon $to = null)
    {
        return $this->variantsStats([$variant->uuid], $from, $to);
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
