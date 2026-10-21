<?php

namespace Remp\CampaignModule\Providers;

use Remp\CampaignModule\Contracts\Remp\SegmentFactory;
use Remp\CampaignModule\Contracts\SegmentAggregator;
use Illuminate\Support\ServiceProvider;

class BeamSegmentsServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if (config('services.remp.beam.segments_addr')) {
            $this->app->tag(SegmentFactory::class, [SegmentAggregator::TAG]);
        }
    }
}
