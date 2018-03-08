<?php

namespace App\Providers;

use App\Contracts\Remp\Segment;
use App\Contracts\SegmentAggregator;
use App\Contracts\SegmentContract;
use GuzzleHttp\Client;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class RempSegmentsServiceProvider extends ServiceProvider
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
                'base_uri' => $app['config']->get('services.remp.beam.segments_addr'),
                'timeout' => 1,
                'connect_timeout' => 1,
            ]);
            return new Segment($client);
        });
        $this->app->tag(Segment::class, SegmentAggregator::TAG);
    }

    public function provides()
    {
        return [SegmentContract::class];
    }
}
