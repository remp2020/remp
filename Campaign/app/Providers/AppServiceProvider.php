<?php

namespace App\Providers;

use App\Contracts\SegmentAggregator;
use GeoIp2\Database\Reader;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Cache\Repository;
use Madewithlove\IlluminatePsrCacheBridge\Laravel\CacheItemPool;
use Psr\Cache\CacheItemPoolInterface;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
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

        $this->app->singleton(CacheItemPoolInterface::class, function ($app) {
            $repository = $app->make(Repository::class);

            return new CacheItemPool($repository);
        });

        $this->app->bind(Reader::class, function (Application $app) {
            return new Reader(config("services.maxmind.database"));
        });

        $this->bindObservers();

        $this->app->bind(SegmentAggregator::class, function (Application $app) {
            return new SegmentAggregator($app->tagged(SegmentAggregator::TAG));
        });
    }

    public function bindObservers()
    {
        \App\Banner::observe(\App\Observers\Banner::class);
        \App\Campaign::observe(\App\Observers\Campaign::class);
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
