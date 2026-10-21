<?php

namespace Remp\CampaignModule\Providers;

use Remp\CampaignModule\Contracts\Crm\SegmentFactory;
use Remp\CampaignModule\Contracts\SegmentAggregator;
use Illuminate\Support\ServiceProvider;

class CrmSegmentServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if (config('services.remp.crm_segment.base_url')) {
            $this->app->tag(SegmentFactory::class, [SegmentAggregator::TAG]);
        }
    }
}
