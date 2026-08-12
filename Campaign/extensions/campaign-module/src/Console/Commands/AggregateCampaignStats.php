<?php

namespace Remp\CampaignModule\Console\Commands;

use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBannerPurchaseStats;
use Remp\CampaignModule\CampaignBannerStats;
use Remp\CampaignModule\Contracts\StatsException;
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

        $total = 0;
        $failures = 0;
        $lastException = null;

        foreach ($campaigns as $campaign) {
            foreach ($campaign->campaignBanners as $campaignBanner) {
                $total++;

                try {
                    $stats = $this->statsHelper->variantStats($campaignBanner, $timeFrom, $timeTo);
                } catch (StatsException $e) {
                    $failures++;
                    $lastException = $e;
                    $this->error(sprintf(
                        "Unable to fetch stats for campaign banner <comment>%s</comment> (campaign <comment>%s</comment>): %s",
                        $campaignBanner->uuid,
                        $campaign->name,
                        $e->getMessage()
                    ));
                    continue;
                }

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

        if ($total > 0 && $failures === $total) {
            throw new StatsException(
                sprintf('All %d campaign banner(s) failed to fetch stats; last error: %s', $total, $lastException->getMessage()),
                0,
                $lastException
            );
        }

        if ($failures > 0) {
            $this->line(sprintf(
                ' <comment>Done with %d/%d banner(s) skipped due to stats API errors; the next run recomputes the same hour bucket.</comment>',
                $failures,
                $total
            ));
        } else {
            $this->line(' <info>OK!</info>');
        }

        return Command::SUCCESS;
    }
}
