<?php

namespace App\Providers;

use App\Contracts\SegmentAggregator;
use GeoIp2\Database\Reader;
use Illuminate\Foundation\Application;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\SerializableClosure;
use Illuminate\Support\ServiceProvider;
use Redis;

class AppServiceProvider extends ServiceProvider
{
    const SEGMENT_AGGREGATOR_REDIS_KEY = 'segment_aggregator';
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (class_exists('Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider')) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

        $this->app->bind(\App\Models\Dimension\Map::class, function ($app) {
            return new \App\Models\Dimension\Map(config('banners.dimensions'));
        });
        $this->app->bind(\App\Models\Position\Map::class, function ($app) {
            return new \App\Models\Position\Map(config('banners.positions'));
        });
        $this->app->bind(\App\Models\Alignment\Map::class, function ($app) {
            return new \App\Models\Alignment\Map(config('banners.alignments'));
        });

        $this->app->bind(Reader::class, function (Application $app) {
            return new Reader(config("services.maxmind.database"));
        });

        $this->bindObservers();

        $this->app->bind(SegmentAggregator::class, function (Application $app) {
            $segmentAggregator = new SegmentAggregator($app->tagged(SegmentAggregator::TAG));

            // SegmentAggregator contains Guzzle clients which have properties defined as closures.
            // It's not possible to serialize closures in plain PHP, but Laravel provides a workaround.
            // This will store a function returning segmentAggregator into the redis which can be later
            // used in plain PHP to bypass Laravel initialization just to get the aggregator.
            $toSerialize = new SerializableClosure(function() use ($segmentAggregator) {
                return $segmentAggregator;
            });
            Redis::set(self::SEGMENT_AGGREGATOR_REDIS_KEY, serialize($toSerialize));

            return $segmentAggregator;
        });
        Paginator::useBootstrapThree();
    }

    public function bindObservers()
    {
        \App\Banner::observe(\App\Observers\Banner::class);
        \App\Campaign::observe(\App\Observers\Campaign::class);
        \App\CampaignBanner::observe(\App\Observers\CampaignBanner::class);
        \App\CampaignSegment::observe(\App\Observers\CampaignSegment::class);
        \App\Schedule::observe(\App\Observers\Schedule::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
