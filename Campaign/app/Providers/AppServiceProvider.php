<?php

namespace App\Providers;

use App\Contracts\SegmentAggregator;
use App\Http\Resources\SearchResource;
use App\Http\Showtime\LazyGeoReader;
use Illuminate\Foundation\Application;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Redis;
use Predis\ClientInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $dimensionMap = new \App\Models\Dimension\Map(config('banners.dimensions'));
        $positionsMap = new \App\Models\Position\Map(config('banners.positions'));
        $alignmentsMap = new \App\Models\Alignment\Map(config('banners.alignments'));

        $this->app->bind(\App\Models\Dimension\Map::class, function () use ($dimensionMap) {
            return $dimensionMap;
        });
        $this->app->bind(\App\Models\Position\Map::class, function () use ($positionsMap) {
            return $positionsMap;
        });
        $this->app->bind(\App\Models\Alignment\Map::class, function () use ($alignmentsMap) {
            return $alignmentsMap;
        });
        $this->app->bind(ClientInterface::class, function () {
            return Redis::connection()->client();
        });
        $this->app->bind(LazyGeoReader::class, function () {
            return new LazyGeoReader(config("services.maxmind.database"));
        });

        $this->app->bind(SegmentAggregator::class, function (Application $app) {
            return new SegmentAggregator($app->tagged(SegmentAggregator::TAG));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bindObservers();

        Paginator::useBootstrapThree();

        SearchResource::withoutWrapping();
    }

    public function bindObservers()
    {
        \App\Banner::observe(\App\Observers\Banner::class);
        \App\Campaign::observe(\App\Observers\Campaign::class);
        \App\CampaignBanner::observe(\App\Observers\CampaignBanner::class);
        \App\CampaignSegment::observe(\App\Observers\CampaignSegment::class);
        \App\Schedule::observe(\App\Observers\Schedule::class);
    }
}
