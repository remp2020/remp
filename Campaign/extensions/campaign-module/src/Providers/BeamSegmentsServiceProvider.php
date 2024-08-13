<?php

namespace Remp\CampaignModule\Providers;

use Remp\CampaignModule\Contracts\Remp\Segment;
use Remp\CampaignModule\Contracts\SegmentAggregator;
use Remp\CampaignModule\Contracts\SegmentContract;
use GuzzleHttp\Client;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class BeamSegmentsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Segment::class, function (Application $app) {
            $client = new Client([
                'base_uri' => config('services.remp.beam.segments_addr'),
                'timeout' => config('services.remp.beam.segments_timeout'),
                'connect_timeout' => 1,
            ]);
            return new Segment($client);
        });
        if (config('services.remp.beam.segments_addr')) {
            $this->app->tag(Segment::class, [SegmentAggregator::TAG]);
        }
    }

    public function provides()
    {
        return [SegmentContract::class];
    }
}
