<?php

namespace App\Providers;

use App\Contracts\Remp\Segment;
use App\Contracts\SegmentAggregator;
use App\Contracts\SegmentContract;
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
