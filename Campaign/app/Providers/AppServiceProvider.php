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
    const SEGMENT_AGGREGATOR_REDIS_KEY = 'segment_aggregator';
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
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


        $this->bindObservers();

        $segmentAggregator = new SegmentAggregator($this->app->tagged(SegmentAggregator::TAG));
        $this->app->bind(SegmentAggregator::class, function (Application $app) use ($segmentAggregator) {
            return $segmentAggregator;
        });

        /** @var \Illuminate\Http\Request $request */
        $request = $this->app->request;
        if (strpos($request->path(), 'showtime') === false) {
            // SegmentAggregator contains Guzzle clients which have properties defined as closures.
            // It's not possible to serialize closures in plain PHP, but Laravel provides a workaround.
            // This will store a function returning segmentAggregator into the redis which can be later
            // used in plain PHP to bypass Laravel initialization just to get the aggregator.
            $serializableSegmentAggregator = $segmentAggregator->getSerializableClosure();
            Redis::set(self::SEGMENT_AGGREGATOR_REDIS_KEY, serialize($serializableSegmentAggregator));

            Redis::set(\App\Models\Dimension\Map::DIMENSIONS_MAP_REDIS_KEY, $dimensionMap->dimensions()->toJson());
            Redis::set(\App\Models\Position\Map::POSITIONS_MAP_REDIS_KEY, $positionsMap->positions()->toJson());
            Redis::set(\App\Models\Alignment\Map::ALIGNMENTS_MAP_REDIS_KEY, $alignmentsMap->alignments()->toJson());
        }

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

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
