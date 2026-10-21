<?php

namespace Remp\CampaignModule\Console\Commands;

use Remp\CampaignModule\Banner;
use Remp\CampaignModule\Contracts\SegmentAggregator;
use Remp\CampaignModule\Snippet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Remp\CampaignModule\Campaign;

class CampaignsRefreshCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:refresh-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes cached campaigns and banners and cache the latest version immediately.';

    /**
     * @var SegmentAggregator
     */
    private $segmentAggregator;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SegmentAggregator $segmentAggregator)
    {
        parent::__construct();
        $this->segmentAggregator = $segmentAggregator;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // segment aggregator config and banner config maps for showtime.php
        $this->segmentAggregator->cacheConfig();
        $this->cacheMaps();

        $activeCampaignIds = Campaign::refreshActiveCampaignsCache();

        foreach (Campaign::whereIn('id', $activeCampaignIds)->get() as $campaign) {
            $this->line(sprintf('Refreshing campaign: <info>%s</info>', $campaign->name));
            $campaign->cache();
        };

        foreach (Banner::all() as $banner) {
            $this->line(sprintf('Refreshing banner: <info>%s</info>', $banner->name));
            $banner->cache();
        }

        Snippet::refreshSnippetsCache();

        $this->line('Campaigns cache refreshed.');
        return 0;
    }

    private function cacheMaps(): void
    {
        $dimensionMap = app(\Remp\CampaignModule\Models\Dimension\Map::class);
        $positionsMap = app(\Remp\CampaignModule\Models\Position\Map::class);
        $alignmentsMap = app(\Remp\CampaignModule\Models\Alignment\Map::class);
        $colorSchemesMap = app(\Remp\CampaignModule\Models\ColorScheme\Map::class);

        Redis::set(\Remp\CampaignModule\Models\Dimension\Map::DIMENSIONS_MAP_REDIS_KEY, $dimensionMap->dimensions()->toJson());
        Redis::set(\Remp\CampaignModule\Models\Position\Map::POSITIONS_MAP_REDIS_KEY, $positionsMap->positions()->toJson());
        Redis::set(\Remp\CampaignModule\Models\Alignment\Map::ALIGNMENTS_MAP_REDIS_KEY, $alignmentsMap->alignments()->toJson());
        Redis::set(\Remp\CampaignModule\Models\ColorScheme\Map::COLOR_SCHEMES_MAP_REDIS_KEY, $colorSchemesMap->colorSchemes()->toJson());
    }
}
