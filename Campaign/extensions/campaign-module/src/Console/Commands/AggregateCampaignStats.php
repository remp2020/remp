<?php

namespace Remp\CampaignModule\Console\Commands;

use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBannerPurchaseStats;
use Remp\CampaignModule\CampaignBannerStats;
use Remp\CampaignModule\Contracts\StatsHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregateCampaignStats extends Command
{
    const COMMAND = 'campaigns:aggregate-stats';

    protected $signature = self::COMMAND . ' {--now=} {--include-inactive}';

    protected $description = 'Reads campaign stats from journal and stores aggregated data';

    private $statsHelper;

    public function __construct(StatsHelper $statsHelper)
    {
        parent::__construct();
        $this->statsHelper = $statsHelper;
    }

    public function handle()
    {
        $now = $this->option('now') ? Carbon::parse($this->option('now')) : Carbon::now();
        $timeFrom = $now->minute(0)->second(0);
        $timeTo = (clone $timeFrom)->addHour();

        $this->line(sprintf("Fetching stats data for campaigns between <info>%s</info> to <info>%s</info>.", $timeFrom, $timeTo));

        $campaigns = Campaign::all();
        if (!$this->option('include-inactive')) {
            $campaigns = $campaigns->filter(function ($item) {
                return $item->active;
            });
        }

        foreach ($campaigns as $campaign) {
            foreach ($campaign->campaignBanners as $campaignBanner) {
                $stats = $this->statsHelper->variantStats($campaignBanner, $timeFrom, $timeTo);

                /** @var CampaignBannerStats $cbs */
                $cbs = CampaignBannerStats::firstOrNew([
                    'campaign_banner_id' => $campaignBanner->id,
                    'time_from' => $timeFrom,
                    'time_to' => $timeTo,
                ]);

                $cbs->click_count = $stats['click_count']->count ?? 0;
                $cbs->show_count = $stats['show_count']->count ?? 0;
                $cbs->payment_count = $stats['payment_count']->count ?? 0;
                $cbs->purchase_count = $stats['purchase_count']->count ?? 0;
                $cbs->save();

                $sums = [];
                foreach ($stats['purchase_sum'] as $sumItem) {
                    $currency = $sumItem->tags->currency ?? null;
                    if ($currency) {
                        if (!array_key_exists($currency, $sums)) {
                            $sums[$currency] = 0.0;
                        }
                        $sums[$currency] += (double) $sumItem->sum;
                    }
                }

                foreach ($sums as $currency => $sum) {
                    $purchaseStat = CampaignBannerPurchaseStats::firstOrNew([
                        'campaign_banner_id' => $campaignBanner->id,
                        'time_from' => $timeFrom,
                        'time_to' => $timeTo,
                        'currency' => $currency
                    ]);

                    /** @var CampaignBannerPurchaseStats $purchaseStat */
                    $purchaseStat->sum = $sum;
                    $purchaseStat->save();
                }
            }
        }

        $this->line(' <info>OK!</info>');
        return 0;
    }
}
