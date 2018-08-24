<?php

namespace App\Providers;

use App\Contracts\Pythia\Segment;
use App\Contracts\SegmentAggregator;
use App\Contracts\SegmentContract;
use GuzzleHttp\Client;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class PythiaSegmentsServiceProvider extends ServiceProvider
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
                'base_uri' => config('services.remp.pythia.segments_addr'),
                'timeout' => 1,
                'connect_timeout' => 1,
            ]);
            return new Segment($client);
        });
        if (config('services.remp.pythia.segments_addr')) {
            $this->app->tag(Segment::class, [SegmentAggregator::TAG]);
        }
    }

    public function provides()
    {
        return [SegmentContract::class];
    }
}
