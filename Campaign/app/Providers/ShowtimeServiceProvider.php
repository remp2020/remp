<?php

namespace App\Providers;

use App\Contracts\SegmentAggregator;
use App\Http\Showtime\LazyDeviceDetector;
use App\Http\Showtime\LazyGeoReader;
use App\Http\Showtime\Showtime;
use App\Http\Showtime\ShowtimeConfig;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class ShowtimeServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Showtime::class, function ($app) {
            $dd = new Showtime(
                Redis::connection()->client(),
                $this->app->get(SegmentAggregator::class),
                $this->app->get(LazyGeoReader::class),
                $this->app->get(ShowtimeConfig::class),
                $this->app->get(LazyDeviceDetector::class),
                $this->app->get(LoggerInterface::class),
            );

            return $dd;
        });
    }

    public function provides()
    {
        return [Showtime::class];
    }
}
